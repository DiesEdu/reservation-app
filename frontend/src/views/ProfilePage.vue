<template>
  <div class="profile-page">
    <div class="container">
      <div class="profile-header">
        <h1>My Profile</h1>
      </div>

      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Loading profile...</p>
      </div>

      <div v-else-if="error" class="error-message">
        <p>{{ error }}</p>
        <button @click="loadProfile" class="btn btn-secondary">Retry</button>
      </div>

      <div v-else class="profile-content">
        <!-- Profile Information Card -->
        <div class="profile-card">
          <div class="card-header">
            <h2>Profile Information</h2>
          </div>
          <div class="card-body">
            <div class="info-group">
              <label>Name</label>
              <p>{{ user?.name || 'Not set' }}</p>
            </div>
            <div class="info-group">
              <label>Email</label>
              <p>{{ user?.email || 'Not set' }}</p>
            </div>
            <div class="info-group">
              <label>Role</label>
              <p class="role-badge" :class="user?.role">{{ user?.role || 'customer' }}</p>
            </div>
            <div class="info-group">
              <label>Email Status</label>
              <div class="verification-status">
                <span v-if="user?.emailVerified" class="status verified">
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="16"
                    height="16"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  >
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                  </svg>
                  Verified
                </span>
                <span v-else class="status unverified">
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="16"
                    height="16"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  >
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                  </svg>
                  Not Verified
                </span>
              </div>
            </div>

            <!-- Resend Verification Email -->
            <div v-if="!user?.emailVerified" class="verification-action">
              <p class="verification-notice">Please verify your email to access all features.</p>
              <button
                @click="resendVerification"
                :disabled="resendingEmail"
                class="btn btn-primary"
              >
                <span v-if="resendingEmail">Sending...</span>
                <span v-else>Resend Verification Email</span>
              </button>
              <p
                v-if="verificationMessage"
                :class="verificationSuccess ? 'success-text' : 'error-text'"
              >
                {{ verificationMessage }}
              </p>
            </div>
          </div>
        </div>

        <!-- Account Actions -->
        <div class="profile-card">
          <div class="card-header">
            <h2>Account Actions</h2>
          </div>
          <div class="card-body">
            <div class="action-buttons">
              <button @click="logout" class="btn btn-danger">
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  width="16"
                  height="16"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                >
                  <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                  <polyline points="16 17 21 12 16 7"></polyline>
                  <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                Logout
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const authStore = useAuthStore()

// State
const loading = ref(true)
const error = ref(null)
const resendingEmail = ref(false)
const verificationMessage = ref('')
const verificationSuccess = ref(false)
const user = ref(null)

// Methods
const loadProfile = async () => {
  loading.value = true
  error.value = null

  try {
    const success = await authStore.fetchCurrentUser()
    if (success) {
      user.value = authStore.user
    } else {
      error.value = 'Failed to load profile. Please login again.'
      setTimeout(() => {
        router.push('/login')
      }, 2000)
    }
  } catch (err) {
    error.value = 'An error occurred while loading your profile.'
    console.error('Profile load error:', err)
  } finally {
    loading.value = false
  }
}

const resendVerification = async () => {
  resendingEmail.value = true
  verificationMessage.value = ''

  try {
    const response = await fetch(
      `${import.meta.env.VITE_API_URL || 'http://localhost:8000/api'}/auth/resend-verification`,
      {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Authorization: `Bearer ${authStore.accessToken}`,
        },
      },
    )

    const data = await response.json()

    if (response.ok) {
      verificationSuccess.value = true
      verificationMessage.value = data.message || 'Verification email sent successfully!'
    } else {
      verificationSuccess.value = false
      verificationMessage.value = data.error || 'Failed to send verification email'
    }
  } catch (err) {
    verificationSuccess.value = false
    verificationMessage.value = 'Network error. Please try again.'
    console.error('Resend verification error:', err)
  } finally {
    resendingEmail.value = false
  }
}

const logout = async () => {
  await authStore.logout()
  router.push('/login')
}

// Lifecycle
onMounted(() => {
  loadProfile()
})
</script>

<style scoped>
.profile-page {
  min-height: 100vh;
  background: #f8f9fa;
  padding: 2rem 1rem;
}

.container {
  max-width: 800px;
  margin: 0 auto;
}

.profile-header {
  margin-bottom: 2rem;
}

.profile-header h1 {
  font-size: 2rem;
  font-weight: 600;
  color: #1a1a1a;
  margin: 0;
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
  color: #6c757d;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 3px solid #e9ecef;
  border-top-color: #4a90d9;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin-bottom: 1rem;
}

.spinner-small {
  width: 24px;
  height: 24px;
  border: 2px solid #e9ecef;
  border-top-color: #4a90d9;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.error-message {
  background: #fff5f5;
  border: 1px solid #feb2b2;
  border-radius: 8px;
  padding: 2rem;
  text-align: center;
  color: #c53030;
}

.profile-content {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.profile-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  overflow: hidden;
}

.card-header {
  background: #f8f9fa;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #e9ecef;
}

.card-header h2 {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1a1a1a;
  margin: 0;
}

.card-body {
  padding: 1.5rem;
}

.info-group {
  margin-bottom: 1.25rem;
}

.info-group:last-child {
  margin-bottom: 0;
}

.info-group label {
  display: block;
  font-size: 0.875rem;
  font-weight: 500;
  color: #6c757d;
  margin-bottom: 0.375rem;
}

.info-group p {
  font-size: 1rem;
  color: #1a1a1a;
  margin: 0;
}

.role-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.875rem;
  font-weight: 500;
  text-transform: capitalize;
}

.role-badge.admin {
  background: #e9d5ff;
  color: #7c3aed;
}

.role-badge.staff {
  background: #dbeafe;
  color: #2563eb;
}

.role-badge.customer {
  background: #d1fae5;
  color: #059669;
}

.verification-status {
  display: flex;
  align-items: center;
}

.status {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  font-size: 0.875rem;
  font-weight: 500;
}

.status.verified {
  color: #059669;
}

.status.unverified {
  color: #dc2626;
}

.verification-action {
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid #e9ecef;
}

.verification-notice {
  font-size: 0.875rem;
  color: #6c757d;
  margin-bottom: 1rem;
}

.success-text {
  color: #059669;
  font-size: 0.875rem;
  margin-top: 0.75rem;
}

.error-text {
  color: #dc2626;
  font-size: 0.875rem;
  margin-top: 0.75rem;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.625rem 1.25rem;
  border: none;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-primary {
  background: #4a90d9;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #3a7bc8;
}

.btn-secondary {
  background: #e9ecef;
  color: #1a1a1a;
}

.btn-secondary:hover:not(:disabled) {
  background: #dee2e6;
}

.btn-danger {
  background: #fee2e2;
  color: #dc2626;
}

.btn-danger:hover:not(:disabled) {
  background: #fecaca;
}

.loading-small {
  display: flex;
  justify-content: center;
  padding: 2rem;
}

.empty-state {
  text-align: center;
  padding: 2rem;
  color: #6c757d;
}

.empty-state svg {
  margin-bottom: 1rem;
  opacity: 0.5;
}

.empty-state p {
  margin-bottom: 1rem;
}

.wishlist-grid {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.wishlist-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem;
  background: #f8f9fa;
  border-radius: 8px;
  transition: background 0.2s ease;
}

.wishlist-item:hover {
  background: #f1f3f5;
}

.wishlist-item-content h3 {
  font-size: 1rem;
  font-weight: 500;
  color: #1a1a1a;
  margin: 0 0 0.25rem;
}

.wishlist-item-content p {
  font-size: 0.875rem;
  color: #6c757d;
  margin: 0 0 0.25rem;
}

.date-added {
  font-size: 0.75rem;
  color: #adb5bd;
}

.btn-remove {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border: none;
  background: transparent;
  color: #6c757d;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-remove:hover {
  background: #fee2e2;
  color: #dc2626;
}

.action-buttons {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

@media (max-width: 640px) {
  .profile-page {
    padding: 1rem;
  }

  .profile-header h1 {
    font-size: 1.5rem;
  }

  .card-body {
    padding: 1rem;
  }

  .action-buttons {
    flex-direction: column;
  }

  .action-buttons .btn {
    width: 100%;
  }
}
</style>
