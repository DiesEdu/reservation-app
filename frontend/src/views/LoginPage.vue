<template>
  <div class="auth-page">
    <div class="auth-container">
      <div class="auth-card">
        <div class="auth-header">
          <div class="logo">
            <i class="bi bi-gem"></i>
            <span>RESONANZ</span>
          </div>
          <h1>Welcome Back</h1>
          <p>Sign in to access your reservations</p>
        </div>

        <form @submit.prevent="handleLogin" class="auth-form">
          <div v-if="error" class="alert alert-danger">
            <i class="bi bi-exclamation-circle"></i>
            {{ error }}
          </div>

          <div v-if="successMessage" class="alert alert-success">
            <i class="bi bi-check-circle"></i>
            {{ successMessage }}
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
                placeholder="Enter your password"
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

          <div class="form-options">
            <label class="remember-me">
              <input type="checkbox" v-model="rememberMe" />
              <span>Remember me</span>
            </label>
            <a href="#" @click.prevent="showForgotPassword" class="forgot-link">
              Forgot password?
            </a>
          </div>

          <button type="submit" class="btn-submit" :disabled="loading">
            <span v-if="loading" class="spinner"></span>
            <span v-else>Sign In</span>
          </button>
        </form>

        <div class="auth-footer">
          <p>
            Don't have an account?
            <router-link to="/register">Create one</router-link>
          </p>
        </div>

        <div class="back-home">
          <router-link to="/">
            <i class="bi bi-arrow-left"></i>
            Back to Home
          </router-link>
        </div>
      </div>

      <!-- Forgot Password Modal -->
      <div
        v-if="showForgotPasswordModal"
        class="modal-overlay"
        @click.self="showForgotPasswordModal = false"
      >
        <div class="modal-content">
          <button class="modal-close" @click="showForgotPasswordModal = false">
            <i class="bi bi-x-lg"></i>
          </button>
          <h2>Reset Password</h2>
          <p>Enter your email address and we'll send you instructions to reset your password.</p>

          <form @submit.prevent="handleForgotPassword">
            <div class="form-group">
              <label for="resetEmail">Email Address</label>
              <div class="input-wrapper">
                <i class="bi bi-envelope"></i>
                <input
                  id="resetEmail"
                  v-model="resetEmail"
                  type="email"
                  placeholder="Enter your email"
                  required
                />
              </div>
            </div>
            <button type="submit" class="btn-submit" :disabled="forgotLoading">
              <span v-if="forgotLoading" class="spinner"></span>
              <span v-else>Send Reset Link</span>
            </button>
          </form>
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
const email = ref('')
const password = ref('')
const rememberMe = ref(false)
const showPassword = ref(false)

// Modal state
const showForgotPasswordModal = ref(false)
const resetEmail = ref('')

// UI state
const loading = ref(false)
const error = ref(null)
const successMessage = ref(null)
const forgotLoading = ref(false)

// Computed
const isAuthenticated = computed(() => authStore.isAuthenticated)

// Methods
const handleLogin = async () => {
  error.value = null
  successMessage.value = null
  loading.value = true

  const result = await authStore.login({
    email: email.value,
    password: password.value,
  })

  loading.value = false

  if (result.success) {
    // Redirect based on role
    const user = authStore.user
    if (user.role === 'admin' || user.role === 'staff') {
      router.push('/analytics')
    } else {
      router.push('/')
    }
  } else {
    error.value = result.error
  }
}

const showForgotPassword = () => {
  showForgotPasswordModal.value = true
}

const handleForgotPassword = async () => {
  forgotLoading.value = true
  error.value = null

  const result = await authStore.forgotPassword(resetEmail.value)

  forgotLoading.value = false

  if (result.success) {
    successMessage.value = result.message
    showForgotPasswordModal.value = false
    resetEmail.value = ''
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

.toggle-password i {
  position: relative;
}

.toggle-password:hover {
  color: #d4af37;
}

.form-options {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  font-size: 0.875rem;
}

.remember-me {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: rgba(244, 229, 194, 0.7);
  cursor: pointer;
}

.remember-me input[type='checkbox'] {
  accent-color: #d4af37;
}

.forgot-link {
  color: #d4af37;
  text-decoration: none;
  transition: opacity 0.3s ease;
}

.forgot-link:hover {
  opacity: 0.8;
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
  opacity: 0.7;
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

/* Modal Styles */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 100;
  padding: 1rem;
}

.modal-content {
  background: rgba(20, 20, 20, 0.95);
  border: 1px solid rgba(212, 175, 55, 0.2);
  border-radius: 16px;
  padding: 2rem;
  max-width: 400px;
  width: 100%;
  position: relative;
}

.modal-close {
  position: absolute;
  top: 1rem;
  right: 1rem;
  background: none;
  border: none;
  color: rgba(244, 229, 194, 0.5);
  cursor: pointer;
  font-size: 1.2rem;
  transition: color 0.3s ease;
}

.modal-close:hover {
  color: #d4af37;
}

.modal-content h2 {
  font-family: 'Playfair Display', serif;
  color: #f4e5c2;
  margin-bottom: 0.5rem;
}

.modal-content p {
  color: rgba(244, 229, 194, 0.6);
  font-size: 0.9rem;
  margin-bottom: 1.5rem;
}
</style>
