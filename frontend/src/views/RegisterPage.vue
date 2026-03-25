<template>
  <div class="auth-page">
    <div class="auth-container">
      <div class="auth-card">
        <div class="auth-header">
          <div class="logo">
            <i class="bi bi-gem"></i>
            <span>RESONANZ</span>
          </div>
          <h1>Create Account</h1>
          <p>Join us for exclusive dining experiences</p>
        </div>

        <form @submit.prevent="handleRegister" class="auth-form">
          <div v-if="error" class="alert alert-danger">
            <i class="bi bi-exclamation-circle"></i>
            {{ error }}
          </div>

          <div v-if="successMessage" class="alert alert-success">
            <i class="bi bi-check-circle"></i>
            {{ successMessage }}
          </div>

          <div class="form-group">
            <label for="name">Full Name</label>
            <div class="input-wrapper">
              <i class="bi bi-person"></i>
              <input
                id="name"
                v-model="name"
                type="text"
                placeholder="Enter your full name"
                required
                :disabled="loading"
              />
            </div>
          </div>

          <div class="form-group">
            <label for="email">Email Address</label>
            <div class="input-wrapper">
              <i class="bi bi-envelope"></i>
              <input
                id="email"
                v-model="email"
                type="email"
                placeholder="Enter your email"
                required
                :disabled="loading"
              />
            </div>
          </div>

          <div class="form-group">
            <label for="password">Password</label>
            <div class="input-wrapper">
              <i class="bi bi-lock"></i>
              <input
                id="password"
                v-model="password"
                :type="showPassword ? 'text' : 'password'"
                placeholder="Create a password (min 6 characters)"
                required
                :disabled="loading"
              />
              <button type="button" class="toggle-password" @click="showPassword = !showPassword">
                <i
                  :class="showPassword ? 'bi bi-eye-slash' : 'bi bi-eye'"
                  style="position: relative"
                ></i>
              </button>
            </div>
          </div>

          <div class="form-group">
            <label for="confirmPassword">Confirm Password</label>
            <div class="input-wrapper">
              <i class="bi bi-lock-fill"></i>
              <input
                id="confirmPassword"
                v-model="confirmPassword"
                :type="showPassword ? 'text' : 'password'"
                placeholder="Confirm your password"
                required
                :disabled="loading"
              />
            </div>
          </div>

          <div class="form-group">
            <label for="role">Account Type</label>
            <div class="role-selector">
              <button
                type="button"
                :class="['role-btn', { active: role === 'customer' }]"
                @click="role = 'customer'"
              >
                <i class="bi bi-person"></i>
                <span>Customer</span>
              </button>
              <button
                type="button"
                :class="['role-btn', { active: role === 'staff' }]"
                @click="role = 'staff'"
              >
                <i class="bi bi-briefcase"></i>
                <span>Staff</span>
              </button>
            </div>
          </div>

          <div class="terms">
            <label>
              <input type="checkbox" v-model="acceptTerms" required />
              <span>
                I agree to the
                <a href="#" @click.prevent>Terms of Service</a>
                and
                <a href="#" @click.prevent>Privacy Policy</a>
              </span>
            </label>
          </div>

          <button type="submit" class="btn-submit" :disabled="loading || !isFormValid">
            <span v-if="loading" class="spinner"></span>
            <span v-else>Create Account</span>
          </button>
        </form>

        <div class="auth-footer">
          <p>
            Already have an account?
            <router-link to="/login">Sign in</router-link>
          </p>
        </div>

        <div class="back-home">
          <router-link to="/">
            <i class="bi bi-arrow-left"></i>
            Back to Home
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const authStore = useAuthStore()

// Form state
const name = ref('')
const email = ref('')
const password = ref('')
const confirmPassword = ref('')
const role = ref('customer')
const acceptTerms = ref(false)
const showPassword = ref(false)

// UI state
const loading = ref(false)
const error = ref(null)
const successMessage = ref(null)

// Computed
const isFormValid = computed(() => {
  return (
    name.value.length >= 2 &&
    email.value.includes('@') &&
    password.value.length >= 6 &&
    password.value === confirmPassword.value &&
    acceptTerms.value
  )
})

const isAuthenticated = computed(() => authStore.isAuthenticated)

// Methods
const handleRegister = async () => {
  error.value = null
  successMessage.value = null

  if (password.value !== confirmPassword.value) {
    error.value = 'Passwords do not match'
    return
  }

  if (password.value.length < 6) {
    error.value = 'Password must be at least 6 characters'
    return
  }

  loading.value = true

  const result = await authStore.register({
    name: name.value,
    email: email.value,
    password: password.value,
    role: role.value,
  })

  loading.value = false

  if (result.success) {
    successMessage.value = 'Account created successfully! Redirecting...'
    // Redirect based on role
    setTimeout(() => {
      router.push('/')
    }, 1500)
  } else {
    error.value = result.error
  }
}

// Redirect if already logged in
if (isAuthenticated.value) {
  const user = authStore.user
  if (user.role === 'admin' || user.role === 'staff') {
    router.push('/analytics')
  } else {
    router.push('/')
  }
}
</script>

<style scoped>
.auth-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  position: relative;
  z-index: 1;
}

.auth-container {
  width: 100%;
  max-width: 440px;
}

.auth-card {
  background: rgba(20, 20, 20, 0.9);
  border: 1px solid rgba(212, 175, 55, 0.2);
  border-radius: 16px;
  padding: 2.5rem;
  backdrop-filter: blur(10px);
}

.auth-header {
  text-align: center;
  margin-bottom: 2rem;
}

.logo {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  font-size: 1.5rem;
  font-weight: 600;
  color: #d4af37;
  letter-spacing: 2px;
  margin-bottom: 1.5rem;
}

.logo i {
  font-size: 1.8rem;
}

.auth-header h1 {
  font-family: 'Playfair Display', serif;
  font-size: 1.8rem;
  color: #f4e5c2;
  margin-bottom: 0.5rem;
}

.auth-header p {
  color: rgba(244, 229, 194, 0.6);
  font-size: 0.95rem;
}

.auth-form {
  margin-bottom: 1.5rem;
}

.form-group {
  margin-bottom: 1.25rem;
}

.form-group label {
  display: block;
  font-size: 0.875rem;
  color: #f4e5c2;
  margin-bottom: 0.5rem;
  font-weight: 500;
}

.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.input-wrapper i {
  position: absolute;
  left: 1rem;
  color: rgba(212, 175, 55, 0.6);
  font-size: 1.1rem;
}

.input-wrapper input {
  width: 100%;
  padding: 0.875rem 1rem 0.875rem 2.75rem;
  background: rgba(10, 10, 10, 0.6);
  border: 1px solid rgba(212, 175, 55, 0.2);
  border-radius: 8px;
  color: #f4e5c2;
  font-size: 1rem;
  transition: all 0.3s ease;
}

.input-wrapper input:focus {
  outline: none;
  border-color: #d4af37;
  box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.1);
}

.input-wrapper input::placeholder {
  color: rgba(244, 229, 194, 0.3);
}

.toggle-password {
  position: absolute;
  right: 2rem;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  color: rgba(212, 175, 55, 0.6);
  cursor: pointer;
  padding: 0.25rem;
  transition: color 0.3s ease;
}

.toggle-password:hover {
  color: #d4af37;
}

.role-selector {
  display: flex;
  gap: 1rem;
}

.role-btn {
  flex: 1;
  padding: 1rem;
  background: rgba(10, 10, 10, 0.6);
  border: 1px solid rgba(212, 175, 55, 0.2);
  border-radius: 8px;
  color: rgba(244, 229, 194, 0.6);
  cursor: pointer;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.3s ease;
}

.role-btn i {
  font-size: 1.5rem;
}

.role-btn.active {
  border-color: #d4af37;
  color: #d4af37;
  background: rgba(212, 175, 55, 0.1);
}

.role-btn:hover:not(.active) {
  border-color: rgba(212, 175, 55, 0.4);
}

.terms {
  margin-bottom: 1.5rem;
}

.terms label {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  color: rgba(244, 229, 194, 0.7);
  font-size: 0.85rem;
  cursor: pointer;
}

.terms input[type='checkbox'] {
  accent-color: #d4af37;
  margin-top: 0.2rem;
}

.terms a {
  color: #d4af37;
  text-decoration: none;
}

.terms a:hover {
  text-decoration: underline;
}

.btn-submit {
  width: 100%;
  padding: 1rem;
  background: linear-gradient(135deg, #d4af37 0%, #b8962e 100%);
  border: none;
  border-radius: 8px;
  color: #0a0a0a;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.btn-submit:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(212, 175, 55, 0.3);
}

.btn-submit:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.spinner {
  width: 20px;
  height: 20px;
  border: 2px solid transparent;
  border-top-color: #0a0a0a;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.alert {
  padding: 1rem;
  border-radius: 8px;
  margin-bottom: 1.5rem;
  font-size: 0.9rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.alert-danger {
  background: rgba(220, 53, 69, 0.1);
  border: 1px solid rgba(220, 53, 69, 0.3);
  color: #f8d7da;
}

.alert-success {
  background: rgba(25, 135, 84, 0.1);
  border: 1px solid rgba(25, 135, 84, 0.3);
  color: #d1e7dd;
}

.auth-footer {
  text-align: center;
  padding-top: 1.5rem;
  border-top: 1px solid rgba(212, 175, 55, 0.1);
}

.auth-footer p {
  color: rgba(244, 229, 194, 0.6);
  font-size: 0.9rem;
}

.auth-footer a {
  color: #d4af37;
  text-decoration: none;
  font-weight: 500;
}

.auth-footer a:hover {
  text-decoration: underline;
}

.back-home {
  text-align: center;
  margin-top: 1.5rem;
}

.back-home a {
  color: rgba(244, 229, 194, 0.5);
  text-decoration: none;
  font-size: 0.9rem;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  transition: color 0.3s ease;
}

.back-home a:hover {
  color: #d4af37;
}
</style>
