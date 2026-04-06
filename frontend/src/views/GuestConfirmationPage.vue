<template>
  <div class="guest-confirmation-page">
    <div class="guest-header">
      <div class="container">
        <h1 class="guest-title">
          <i class="bi bi-person-check"></i>
          Guest Confirmation
        </h1>
        <p class="guest-subtitle">Please wait for staff to search your reservation</p>
      </div>
    </div>

    <main class="guest-content">
      <div class="container">
        <!-- Connection Status -->
        <div class="status-card" :class="{ connected: isConnected, disconnected: !isConnected }">
          <div class="status-indicator">
            <span class="status-dot"></span>
            <span class="status-text">{{ connectionStatus }}</span>
          </div>
          <div v-if="lastUpdate" class="last-update">
            <i class="bi bi-clock"></i>
            Last update: {{ formatTime(lastUpdate) }}
          </div>
        </div>

        <!-- Verified Popup -->
        <transition name="popup">
          <div v-if="showVerifiedPopup" class="verified-popup">
            <div class="verified-popup-content">
              <div class="confetti-container">
                <div v-for="i in 20" :key="i" class="confetti" :style="{ '--delay': `${i * 0.1}s`, '--x': `${Math.random() * 100}%` }"></div>
              </div>
              <div class="popup-icon">
                <i class="bi bi-check-circle-fill"></i>
              </div>
              <h3>Verification Complete</h3>
              <div class="popup-details">
                <div class="popup-seat" v-if="results[0]?.seat_code">
                  <span class="popup-label">Table</span>
                  <span class="popup-value-large">{{ results[0].seat_code }}</span>
                </div>
                <div class="popup-info" v-if="results[0]?.name">
                  <span class="popup-label">Name</span>
                  <span class="popup-value">{{ results[0].name }}</span>
                </div>
                <div class="popup-info" v-if="results[0]?.company">
                  <span class="popup-label">Company</span>
                  <span class="popup-value">{{ results[0].company }}</span>
                </div>
                <div class="popup-info" v-if="results[0]?.position">
                  <span class="popup-label">Position</span>
                  <span class="popup-value">{{ results[0].position }}</span>
                </div>
              </div>
            </div>
          </div>
        </transition>

        <!-- Email Filter -->
        <div class="email-filter-card">
          <label for="email-filter" class="filter-label">
            <i class="bi bi-person-badge"></i>
            Filter by Staff Email
          </label>
          <select
            id="email-filter"
            v-model="selectedEmail"
            class="email-select"
          >
            <option value="">All Staff</option>
            <option v-for="user in users" :key="user.id" :value="user.email">
              {{ user.email }}
            </option>
          </select>
        </div>

        <!-- Search Results Display -->
        <div class="results-section">
          <div v-if="searchQuery" class="search-info">
            <p>Searching for: <strong>"{{ searchQuery }}"</strong></p>
            <p class="result-count">{{ results.length }} guest(s) found</p>
          </div>

          <!-- Loading State -->
          <div v-if="isLoading" class="loading-state">
            <div class="spinner"></div>
            <p>Searching database...</p>
          </div>

          <!-- Results List -->
          <div v-if="results.length > 0" class="results-list">
            <div
              v-for="guest in results"
              :key="guest.id"
              class="guest-card"
              :class="{ verified: guest.verified }"
            >
              <div class="guest-avatar">
                {{ guest.name.charAt(0).toUpperCase() }}
              </div>
              <div class="guest-details">
                <h3 class="guest-name">{{ guest.name }}</h3>
                <p v-if="guest.company" class="guest-company">{{ guest.company }}</p>
                <p v-if="guest.position" class="guest-position">{{ guest.position }}</p>
                <div class="guest-meta">
                  <span v-if="guest.seat_code" class="table-badge">
                    <i class="bi bi-grid-3x3-gap"></i>
                    Table {{ guest.seat_code }}
                  </span>
                  <span class="status-badge" :class="guest.status">
                    {{ guest.status }}
                  </span>
                </div>
              </div>
              <div class="guest-verified">
                <span v-if="guest.verified" class="verified-status">
                  <i class="bi bi-check-circle-fill"></i>
                  Verified
                </span>
                <span v-else class="not-verified">
                  <i class="bi bi-hourglass"></i>
                  Pending
                </span>
              </div>
            </div>
          </div>

          <!-- No Results -->
          <div v-else-if="searchQuery && !isLoading && !isConnected" class="no-results">
            <i class="bi bi-search"></i>
            <p>Waiting for search from staff...</p>
          </div>

          <!-- Verified Confirmation Section -->
          <div v-else-if="isVerified && results.length > 0" class="verified-section">
            <div class="verified-card">
              <div class="verified-header">
                <div class="checkmark-circle">
                  <i class="bi bi-check-lg"></i>
                </div>
                <h2>Reservation Confirmed!</h2>
                <p class="verified-subtitle">Your table is ready</p>
              </div>

              <div class="verified-details">
                <div v-for="guest in results" :key="guest.id" class="guest-info">
                  <div class="table-highlight">
                    <span class="table-label">Table</span>
                    <span class="table-value">{{ guest.seatCode || '-' }}</span>
                  </div>
                  <div class="guest-name">
                    <span class="name-label">Guest</span>
                    <span class="name-value">{{ guest.name }}</span>
                  </div>
                  <div v-if="guest.company" class="guest-company">
                    <span class="name-label">Company</span>
                    <span class="subname-value">{{ guest.company }}</span>
                  </div>
                  <div v-if="guest.position" class="guest-position">
                    <span class="name-label">Position</span>
                    <span class="subname-value">{{ guest.position }}</span>
                  </div>
                </div>
              </div>

              <div class="success-message">
                <i class="bi bi-stars"></i>
                <span>We're excited to welcome you!</span>
              </div>
            </div>

            <div class="countdown-timer">
              <i class="bi bi-clock-history"></i>
              <span>Returning in <strong>{{ countdown }}</strong> seconds</span>
            </div>
          </div>

          <!-- Waiting State -->
          <div v-else-if="!searchQuery && !isVerified" class="waiting-state">
            <div class="waiting-animation">
              <i class="bi bi-person-badge"></i>
            </div>
            <h3>Waiting for Staff</h3>
            <p>When a staff member searches for your name, your reservation will appear here in real-time.</p>
            <div class="tips">
              <h4>Tips:</h4>
              <ul>
                <li>Make sure your name matches the reservation exactly</li>
                <li>Check your email for confirmation</li>
                <li>Contact staff if you have any issues</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'

const isConnected = ref(false)
const isLoading = ref(false)
const searchQuery = ref('')
const results = ref([])
const lastUpdate = ref(null)
const pollInterval = ref(null)
const lastPollTime = ref(0)
const lastUserEmail = ref('')
const connectionStatus = ref('Connecting...')

// Email filter for selecting which user's search to view
const users = ref([])
const selectedEmail = ref('')

// Verified state for showing reservation details
const isVerified = ref(false)
const countdown = ref(0)
const countdownInterval = ref(null)

// Show verified popup
const showVerifiedPopup = ref(false)

// Confetti animation state
const showConfetti = ref(false)

// Fetch user list for dropdown
const fetchUsers = async () => {
  try {
    const response = await fetch(`${API_URL}/get-users`)
    const data = await response.json()
    if (data.success) {
      users.value = data.data || []
    }
  } catch (err) {
    console.error('Failed to fetch users:', err)
  }
}

// Poll for search results
const pollForResults = async () => {
  try {
    let url = `${API_URL}/get-latest-search`
    if (selectedEmail.value) {
      url += `?email=${encodeURIComponent(selectedEmail.value)}`
    }
    const response = await fetch(url)
    const data = await response.json()

    // Mark as connected if we get any response (even empty)
    isConnected.value = true
    connectionStatus.value = 'Live'

    // If no search data (cleaned after 1 minute), clear local state
    if (!data.search || data.search === '') {
      if (searchQuery.value !== '') {
        searchQuery.value = ''
        results.value = []
        lastPollTime.value = 0
        isVerified.value = false
      }
      return
    }

    if (data.success && data.search && data.search !== lastPollTime.value) {
      lastPollTime.value = data.search
      lastUserEmail.value = data.user_email || ''
      searchQuery.value = data.search
      results.value = data.results || []
      lastUpdate.value = new Date()
      isLoading.value = false

      // Check if verified is true
      if (data.verified === true) {
        isVerified.value = true
        showVerifiedNotification()
        startCountdown()
      }
    }
  } catch (err) {
    console.error('Polling error:', err)
    isConnected.value = false
    connectionStatus.value = 'Reconnecting...'
  }
}

const startCountdown = () => {
  countdown.value = 5
  if (countdownInterval.value) {
    clearInterval(countdownInterval.value)
  }
  countdownInterval.value = setInterval(async () => {
    countdown.value--
    if (countdown.value <= 0) {
      clearInterval(countdownInterval.value)
      // Clear sse_events before resetting
      await clearSseEvents()
      resetToInitial()
    }
  }, 1000)
}

const clearSseEvents = async () => {
  if (!lastUserEmail.value) return
  try {
    await fetch(`${API_URL}/clear-sse-events?email=${encodeURIComponent(lastUserEmail.value)}`, {
      method: 'POST',
    })
  } catch (err) {
    console.error('Failed to clear sse events:', err)
  }
}

const triggerConfetti = () => {
  showConfetti.value = true
  setTimeout(() => {
    showConfetti.value = false
  }, 3000)
}

const showVerifiedNotification = () => {
  showVerifiedPopup.value = true
  triggerConfetti()
  setTimeout(async () => {
    showVerifiedPopup.value = false
    // Clean sse_events by user_email from filter
    if (selectedEmail.value) {
      try {
        await fetch(`${API_URL}/clear-sse-events?email=${encodeURIComponent(selectedEmail.value)}`, {
          method: 'POST',
        })
      } catch (err) {
        console.error('Failed to clear sse events:', err)
      }
    }
  }, 5000)
}

const resetToInitial = () => {
  isVerified.value = false
  searchQuery.value = ''
  results.value = []
  lastPollTime.value = 0
  lastUpdate.value = null
}

const startPolling = () => {
  connectionStatus.value = 'Connecting...'
  // Poll immediately on connect
  pollForResults()
  // Then poll every 2 seconds
  pollInterval.value = setInterval(pollForResults, 2000)
}

const formatTime = (date) => {
  return new Date(date).toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  })
}

onMounted(async () => {
  await fetchUsers()
  startPolling()
})

onUnmounted(() => {
  if (pollInterval.value) {
    clearInterval(pollInterval.value)
  }
  if (countdownInterval.value) {
    clearInterval(countdownInterval.value)
  }
})
</script>

<style scoped>
.guest-confirmation-page {
  min-height: 100vh;
  background: linear-gradient(180deg, #f0f4ff 0%, #ffffff 50%, #fff5f0 100%);
}

.guest-header {
  background: linear-gradient(135deg, var(--primary, #1f4fa3) 0%, var(--accent, #ff9f43) 100%);
  padding: 2.5rem 0;
  text-align: center;
}

.guest-title {
  font-family: 'Playfair Display', serif;
  font-size: 2.2rem;
  font-weight: 700;
  color: #ffffff;
  margin: 0 0 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
}

.guest-title i {
  font-size: 1.8rem;
}

.guest-subtitle {
  color: rgba(255, 255, 255, 0.9);
  font-size: 1.1rem;
  margin: 0;
}

.guest-content {
  padding: 2rem 0;
  max-width: 700px;
  margin: 0 auto;
}

.status-card {
  background: #ffffff;
  border-radius: 12px;
  padding: 1rem 1.5rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  margin-bottom: 2rem;
}

.email-filter-card {
  background: #ffffff;
  border-radius: 12px;
  padding: 1rem 1.5rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  margin-bottom: 2rem;
  flex-wrap: wrap;
}

.filter-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 600;
  color: var(--primary-dark, #0f172a);
  font-size: 0.95rem;
}

.email-select {
  flex: 1;
  min-width: 200px;
  padding: 0.65rem 1rem;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  font-size: 0.95rem;
  color: var(--primary-dark, #0f172a);
  background: #ffffff;
  cursor: pointer;
}

.email-select:focus {
  outline: none;
  border-color: var(--primary, #1f4fa3);
  box-shadow: 0 0 0 3px rgba(31, 79, 163, 0.12);
}

.status-indicator {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.status-dot {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  animation: pulse 2s infinite;
}

.status-card.connected .status-dot {
  background: #22c55e;
}

.status-card.disconnected .status-dot {
  background: #f59e0b;
  animation: blink 1s infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.7; transform: scale(1.1); }
}

@keyframes blink {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.3; }
}

.status-text {
  font-weight: 600;
  color: var(--primary-dark, #0f172a);
}

.last-update {
  color: #64748b;
  font-size: 0.85rem;
  display: flex;
  align-items: center;
  gap: 0.35rem;
}

.search-info {
  text-align: center;
  margin-bottom: 1.5rem;
}

.search-info p {
  color: #64748b;
  margin: 0.25rem 0;
}

.search-info strong {
  color: var(--primary-dark, #0f172a);
}

.result-count {
  font-weight: 600;
  color: var(--primary, #1f4fa3) !important;
}

.loading-state {
  text-align: center;
  padding: 3rem;
}

.spinner {
  width: 48px;
  height: 48px;
  border: 4px solid #e2e8f0;
  border-top-color: var(--primary, #1f4fa3);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.results-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.guest-card {
  background: #ffffff;
  border: 2px solid #e2e8f0;
  border-radius: 16px;
  padding: 1.25rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  transition: all 0.3s ease;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.guest-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(31, 79, 163, 0.12);
}

.guest-card.verified {
  border-color: #22c55e;
  background: linear-gradient(135deg, #ffffff 0%, #f0fdf4 100%);
}

.guest-avatar {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: linear-gradient(135deg, #ff9f43 0%, #ffd84d 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.4rem;
  font-weight: 700;
  color: #0f172a;
  flex-shrink: 0;
}

.guest-details {
  flex: 1;
}

.guest-name {
  font-size: 1.2rem;
  font-weight: 700;
  color: var(--primary-dark, #0f172a);
  margin: 0 0 0.25rem;
}

.guest-company {
  color: #64748b;
  font-size: 0.95rem;
  margin: 0;
}

.guest-position {
  color: #94a3b8;
  font-size: 0.85rem;
  margin: 0.15rem 0 0;
}

.guest-meta {
  display: flex;
  gap: 0.75rem;
  margin-top: 0.5rem;
  flex-wrap: wrap;
}

.table-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  background: #e0e7ff;
  color: #4338ca;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
}

.status-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: capitalize;
}

.status-badge.confirmed {
  background: #dcfce7;
  color: #166534;
}

.status-badge.pending {
  background: #fef3c7;
  color: #92400e;
}

.status-badge.cancelled {
  background: #fee2e2;
  color: #991b1b;
}

.guest-verified {
  text-align: right;
}

.verified-status {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  color: #15803d;
  font-weight: 600;
  font-size: 0.9rem;
}

.verified-status i {
  font-size: 1.2rem;
}

.not-verified {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  color: #92400e;
  font-weight: 500;
  font-size: 0.85rem;
}

.not-verified i {
  font-size: 1rem;
}

.no-results {
  text-align: center;
  padding: 3rem;
  color: #94a3b8;
}

.no-results i {
  font-size: 3rem;
  display: block;
  margin-bottom: 1rem;
  color: var(--primary, #1f4fa3);
}

.waiting-state {
  text-align: center;
  padding: 3rem 2rem;
}

.waiting-animation {
  width: 120px;
  height: 120px;
  margin: 0 auto 1.5rem;
  background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.waiting-animation i {
  font-size: 3rem;
  color: var(--primary, #1f4fa3);
  animation: float 3s ease-in-out infinite;
}

@keyframes float {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-10px); }
}

.waiting-state h3 {
  font-size: 1.5rem;
  color: var(--primary-dark, #0f172a);
  margin: 0 0 0.75rem;
}

.waiting-state p {
  color: #64748b;
  font-size: 1rem;
  max-width: 400px;
  margin: 0 auto 2rem;
  line-height: 1.6;
}

.tips {
  background: #f8fafc;
  border-radius: 12px;
  padding: 1.5rem;
  text-align: left;
  max-width: 400px;
  margin: 0 auto;
}

.tips h4 {
  font-size: 1rem;
  color: var(--primary-dark, #0f172a);
  margin: 0 0 0.75rem;
}

.tips ul {
  margin: 0;
  padding-left: 1.25rem;
}

.tips li {
  color: #64748b;
  margin: 0.5rem 0;
  line-height: 1.5;
}

/* Verified Confirmation Section */
.verified-section {
  text-align: center;
}

.verified-card {
  background: #ffffff;
  border-radius: 20px;
  padding: 2rem;
  box-shadow: 0 8px 32px rgba(34, 197, 94, 0.15);
  border: 2px solid #22c55e;
}

.verified-header {
  margin-bottom: 1.5rem;
}

.checkmark-circle {
  width: 72px;
  height: 72px;
  border-radius: 50%;
  background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1rem;
}

.checkmark-circle i {
  font-size: 2rem;
  color: #ffffff;
}

.verified-header h2 {
  font-size: 1.8rem;
  font-weight: 700;
  color: var(--primary-dark, #0f172a);
  margin: 0 0 0.5rem;
}

.verified-subtitle {
  color: #64748b;
  font-size: 1rem;
  margin: 0;
}

.verified-details {
  background: #f8fafc;
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 1.5rem;
}

.guest-info {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.table-highlight {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
}

.table-label {
  font-size: 0.85rem;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.table-value {
  font-size: 3rem;
  font-weight: 800;
  color: var(--primary-dark, #0f172a);
}

.guest-name, .guest-company, .guest-position {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
}

.name-label {
  font-size: 0.85rem;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.name-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--primary-dark, #0f172a);
}

.subname-value {
  font-size: 1.1rem;
  color: #64748b;
}

.success-message {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  color: #15803d;
  font-weight: 600;
  font-size: 1.1rem;
}

.success-message i {
  font-size: 1.3rem;
}

.countdown-timer {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  margin-top: 1.5rem;
  color: #64748b;
  font-size: 0.95rem;
}

.countdown-timer i {
  color: var(--primary, #1f4fa3);
}

.countdown-timer strong {
  color: var(--primary, #1f4fa3);
}

.container {
  padding: 0 1.5rem;
}

/* Verified Popup */
.verified-popup {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 0, 0, 0.5);
  z-index: 1000;
}

.verified-popup-content {
  background: linear-gradient(135deg, #ffffff 0%, #f0fdf4 100%);
  border-radius: 24px;
  padding: 2.5rem;
  text-align: center;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
  border: 3px solid #22c55e;
  position: relative;
  overflow: hidden;
  min-width: 300px;
}

.popup-icon {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1.5rem;
  animation: pop 0.5s ease-out;
}

.popup-icon i {
  font-size: 2.5rem;
  color: #ffffff;
}

.verified-popup-content h3 {
  font-size: 1.5rem;
  font-weight: 700;
  color: #15803d;
  margin: 0 0 1.5rem;
}

.popup-details {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.popup-seat {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
  margin-bottom: 0.5rem;
}

.popup-label {
  font-size: 0.75rem;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.popup-value-large {
  font-size: 2.5rem;
  font-weight: 800;
  color: #0f172a;
  line-height: 1;
}

.popup-info {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.15rem;
}

.popup-value {
  font-size: 1.1rem;
  font-weight: 600;
  color: #334155;
}

/* Confetti Animation */
.confetti-container {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 100%;
  overflow: hidden;
  pointer-events: none;
}

.confetti {
  position: absolute;
  width: 10px;
  height: 10px;
  top: -20px;
  left: var(--x);
  background: var(--confetti-color, #22c55e);
  animation: confetti-fall 3s ease-out forwards;
  animation-delay: var(--delay, 0s);
  border-radius: 2px;
  opacity: 0;
}

.confetti:nth-child(odd) {
  --confetti-color: #ff9f43;
  width: 8px;
  height: 12px;
}

.confetti:nth-child(3n) {
  --confetti-color: #3b82f6;
}

.confetti:nth-child(4n) {
  --confetti-color: #ec4899;
}

@keyframes confetti-fall {
  0% {
    opacity: 1;
    transform: translateY(0) rotate(0deg) scale(1);
  }
  100% {
    opacity: 0;
    transform: translateY(400px) rotate(720deg) scale(0.5);
  }
}

@keyframes pop {
  0% {
    transform: scale(0);
  }
  70% {
    transform: scale(1.2);
  }
  100% {
    transform: scale(1);
  }
}

.popup-enter-active {
  animation: popup-in 0.3s ease-out;
}

.popup-leave-active {
  animation: popup-in 0.2s ease-in reverse;
}

@keyframes popup-in {
  from {
    opacity: 0;
    transform: scale(0.8);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

@media (max-width: 640px) {
  .guest-title {
    font-size: 1.6rem;
  }

  .guest-card {
    flex-direction: column;
    text-align: center;
  }

  .guest-meta {
    justify-content: center;
  }

  .guest-verified {
    text-align: center;
  }
}
</style>
