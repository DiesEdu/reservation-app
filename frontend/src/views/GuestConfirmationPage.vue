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
                  <span v-if="guest.table_preference" class="table-badge">
                    <i class="bi bi-grid-3x3-gap"></i>
                    Table {{ guest.table_preference }}
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

          <!-- Waiting State -->
          <div v-else-if="!searchQuery" class="waiting-state">
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
const connectionStatus = ref('Connecting...')

// Email filter for selecting which user's search to view
const userEmail = ref('')
const users = ref([])
const selectedEmail = ref('')

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
    
    if (data.success && data.search && data.search !== lastPollTime.value) {
      lastPollTime.value = data.search
      searchQuery.value = data.search
      results.value = data.results || []
      lastUpdate.value = new Date()
      isLoading.value = false
    }
  } catch (err) {
    console.error('Polling error:', err)
    isConnected.value = false
    connectionStatus.value = 'Reconnecting...'
  }
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

.container {
  padding: 0 1.5rem;
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