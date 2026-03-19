import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

// API Base URL
const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'

export const useAuthStore = defineStore('auth', () => {
  // State
  const user = ref(null)
  const accessToken = ref(localStorage.getItem('accessToken') || null)
  const refreshToken = ref(localStorage.getItem('refreshToken') || null)
  const loading = ref(false)
  const error = ref(null)

  // Computed
  const isAuthenticated = computed(() => !!accessToken.value && !!user.value)
  const isAdmin = computed(() => user.value?.role === 'admin')
  const isStaff = computed(() => user.value?.role === 'staff')
  const canAccessConfirmation = computed(
    () => user.value?.role === 'admin' || user.value?.role === 'staff',
  )
  const userName = computed(() => user.value?.name || 'Guest')
  const userEmail = computed(() => user.value?.email || '')

  // Actions

  /**
   * Register a new user
   */
  const register = async (userData) => {
    loading.value = true
    error.value = null

    try {
      const response = await fetch(`${API_URL}/auth/register`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          email: userData.email,
          password: userData.password,
          name: userData.name,
          role: userData.role || 'user',
        }),
      })

      const data = await response.json()

      if (data.success) {
        // Store tokens
        accessToken.value = data.data.accessToken
        refreshToken.value = data.data.refreshToken
        localStorage.setItem('accessToken', data.data.accessToken)
        localStorage.setItem('refreshToken', data.data.refreshToken)

        // Store user
        user.value = data.data.user

        return { success: true }
      } else {
        error.value = data.error || 'Registration failed'
        return { success: false, error: data.error }
      }
    } catch (err) {
      error.value = 'Network error: Unable to connect to server'
      console.error('Registration error:', err)
      return { success: false, error: error.value }
    } finally {
      loading.value = false
    }
  }

  /**
   * Login user
   */
  const login = async (credentials) => {
    loading.value = true
    error.value = null

    try {
      const response = await fetch(`${API_URL}/auth/login`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          email: credentials.email,
          password: credentials.password,
        }),
      })

      const data = await response.json()

      if (data.success) {
        // Store tokens
        accessToken.value = data.data.accessToken
        refreshToken.value = data.data.refreshToken
        localStorage.setItem('accessToken', data.data.accessToken)
        localStorage.setItem('refreshToken', data.data.refreshToken)

        // Store user
        user.value = data.data.user

        return { success: true }
      } else {
        error.value = data.error || 'Login failed'
        return { success: false, error: data.error }
      }
    } catch (err) {
      error.value = 'Network error: Unable to connect to server'
      console.error('Login error:', err)
      return { success: false, error: error.value }
    } finally {
      loading.value = false
    }
  }

  /**
   * Logout user
   */
  const logout = async () => {
    if (accessToken.value) {
      try {
        await fetch(`${API_URL}/auth/logout`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            Authorization: `Bearer ${accessToken.value}`,
          },
        })
      } catch (err) {
        console.error('Logout error:', err)
      }
    }

    // Clear state
    user.value = null
    accessToken.value = null
    refreshToken.value = null
    localStorage.removeItem('accessToken')
    localStorage.removeItem('refreshToken')
  }

  /**
   * Refresh access token
   */
  const refreshAccessToken = async () => {
    if (!refreshToken.value) {
      return false
    }

    try {
      const response = await fetch(`${API_URL}/auth/refresh`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          refreshToken: refreshToken.value,
        }),
      })

      const data = await response.json()

      if (data.success) {
        accessToken.value = data.data.accessToken
        refreshToken.value = data.data.refreshToken
        localStorage.setItem('accessToken', data.data.accessToken)
        localStorage.setItem('refreshToken', data.data.refreshToken)
        return true
      } else {
        // Refresh failed, logout user
        await logout()
        return false
      }
    } catch (err) {
      console.error('Token refresh error:', err)
      await logout()
      return false
    }
  }

  /**
   * Fetch current user data
   */
  const fetchCurrentUser = async () => {
    if (!accessToken.value) {
      return false
    }

    try {
      const response = await fetch(`${API_URL}/auth/me`, {
        method: 'GET',
        headers: {
          Authorization: `Bearer ${accessToken.value}`,
        },
      })

      if (response.status === 401) {
        // Token expired, try to refresh
        const refreshed = await refreshAccessToken()
        if (refreshed) {
          return await fetchCurrentUser()
        }
        return false
      }

      const data = await response.json()

      if (data.success) {
        user.value = data.data.user
        return true
      } else {
        return false
      }
    } catch (err) {
      console.error('Fetch user error:', err)
      return false
    }
  }

  /**
   * Initialize auth state - check if user is logged in
   */
  const initializeAuth = async () => {
    if (accessToken.value) {
      const success = await fetchCurrentUser()
      if (!success) {
        // Clear invalid tokens
        accessToken.value = null
        refreshToken.value = null
        localStorage.removeItem('accessToken')
        localStorage.removeItem('refreshToken')
      }
    }
  }

  /**
   * Forgot password
   */
  const forgotPassword = async (email) => {
    loading.value = true
    error.value = null

    try {
      const response = await fetch(`${API_URL}/auth/forgot-password`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ email }),
      })

      const data = await response.json()

      if (data.success) {
        return { success: true, message: data.message }
      } else {
        error.value = data.error
        return { success: false, error: data.error }
      }
    } catch (err) {
      error.value = 'Network error: Unable to connect to server'
      return { success: false, error: error.value }
    } finally {
      loading.value = false
    }
  }

  /**
   * Reset password
   */
  const resetPassword = async (token, newPassword) => {
    loading.value = true
    error.value = null

    try {
      const response = await fetch(`${API_URL}/auth/reset-password`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          token,
          password: newPassword,
        }),
      })

      const data = await response.json()

      if (data.success) {
        return { success: true, message: data.message }
      } else {
        error.value = data.error
        return { success: false, error: data.error }
      }
    } catch (err) {
      error.value = 'Network error: Unable to connect to server'
      return { success: false, error: error.value }
    } finally {
      loading.value = false
    }
  }

  return {
    // State
    user,
    accessToken,
    refreshToken,
    loading,
    error,

    // Computed
    isAuthenticated,
    isAdmin,
    isStaff,
    canAccessConfirmation,
    userName,
    userEmail,

    // Actions
    register,
    login,
    logout,
    refreshAccessToken,
    fetchCurrentUser,
    initializeAuth,
    forgotPassword,
    resetPassword,
  }
})
