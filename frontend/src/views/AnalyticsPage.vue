<template>
  <div class="analytics-page">
    <Navbar />
    <!-- Access Denied Message -->
    <div v-if="accessDenied" class="access-denied-container">
      <div class="access-denied-card">
        <i class="bi bi-shield-exclamation"></i>
        <h2>Access Restricted</h2>
        <p>You don't have permission to access this page.</p>
        <p class="access-note">Only admin and staff roles can access the analytics page.</p>
        <button @click="$router.push('/')" class="btn-home">
          <i class="bi bi-house"></i>
          <span>Go to Home</span>
        </button>
      </div>
    </div>

    <div v-else class="analytics-page-content">
      <!-- Header -->
      <header class="analytics-header">
        <div class="container">
          <div class="header-content">
            <div class="header-text">
              <h1 class="page-title">
                <i class="bi bi-graph-up-arrow"></i>
                Analytics
              </h1>
              <p class="page-subtitle">Insights and performance metrics</p>
            </div>
            <div class="header-actions">
              <select v-model="timeFilter" class="filter-select">
                <option value="all">All Time</option>
                <option value="today">Today</option>
                <option value="week">This Week</option>
                <option value="month">This Month</option>
              </select>
            </div>
          </div>
        </div>
      </header>

      <main class="analytics-content">
        <div class="container">
          <!-- Summary Stats -->
          <section class="summary-section">
            <div class="stats-grid">
              <div
                v-for="(stat, index) in summaryStats"
                :key="stat.label"
                class="stat-card"
                :style="{ animationDelay: `${index * 0.1}s` }"
              >
                <div class="stat-icon" :class="stat.color">
                  <i :class="stat.icon"></i>
                </div>
                <div class="stat-details">
                  <div class="stat-value">{{ stat.value }}</div>
                  <div class="stat-label">{{ stat.label }}</div>
                  <div class="stat-trend" :class="stat.trendClass" v-if="stat.trend">
                    <i :class="stat.trendIcon"></i>
                    {{ stat.trend }}
                  </div>
                </div>
              </div>
            </div>
          </section>

          <!-- Search and Filter Table -->
          <section class="search-section">
            <div class="chart-card">
              <div class="chart-header">
                <h3 class="chart-title">
                  <i class="bi bi-search"></i>
                  Customer Search & Reservations
                </h3>
              </div>
              <div class="chart-body">
                <!-- Search and Filters -->
                <div class="search-filters">
                  <div class="search-input-group">
                    <i class="bi bi-search"></i>
                    <input
                      v-model="searchQuery"
                      type="text"
                      placeholder="Search by name, email, or phone..."
                      class="search-input"
                    />
                  </div>
                  <div class="filter-group">
                    <select v-model="statusFilter" class="filter-select">
                      <option value="">All Status</option>
                      <option value="pending">Pending</option>
                      <option value="confirmed">Confirmed</option>
                      <option value="cancelled">Cancelled</option>
                    </select>
                    <input
                      v-model="tableFilter"
                      list="table-options"
                      class="filter-select"
                      placeholder="All Tables"
                    />
                    <datalist id="table-options">
                      <option value=""></option>
                      <option v-for="table in tableOptions" :key="table" :value="table"></option>
                    </datalist>
                    <input
                      v-model="dateFilter"
                      type="date"
                      class="date-input"
                      placeholder="Filter by date"
                    />
                    <button @click="clearFilters" class="btn-clear">
                      <i class="bi bi-x-circle"></i>
                      Clear
                    </button>
                  </div>
                </div>

                <!-- Results Count -->
                <div class="results-info">
                  <span
                    >Showing {{ searchResults.length }} of
                    {{ filteredReservations.length }} reservations</span
                  >
                </div>

                <!-- Data Table -->
                <div class="table-responsive">
                  <table class="data-table">
                    <thead>
                      <tr>
                        <th @click="sortBy('name')" class="sortable">
                          Customer
                          <i v-if="sortField === 'name'" :class="sortIcon"></i>
                        </th>
                        <th>Contact</th>
                        <th @click="sortBy('date')" class="sortable">
                          Date
                          <i v-if="sortField === 'date'" :class="sortIcon"></i>
                        </th>
                        <th @click="sortBy('time')" class="sortable">
                          Time
                          <i v-if="sortField === 'time'" :class="sortIcon"></i>
                        </th>
                        <th @click="sortBy('guests')" class="sortable">
                          Guests
                          <i v-if="sortField === 'guests'" :class="sortIcon"></i>
                        </th>
                        <th>Table</th>
                        <th @click="sortBy('status')" class="sortable">
                          Status
                          <i v-if="sortField === 'status'" :class="sortIcon"></i>
                        </th>
                        <th>Verified</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="reservation in paginatedResults" :key="reservation.id">
                        <td>
                          <div class="customer-cell">
                            <div class="customer-avatar">
                              {{ reservation.name.charAt(0).toUpperCase() }}
                            </div>
                            <div class="customer-info">
                              <span class="customer-name">{{ reservation.name }}</span>
                              <span class="customer-company">{{ reservation.company || '-' }}</span>
                            </div>
                          </div>
                        </td>
                        <td>
                          <div class="contact-cell">
                            <span>{{ reservation.email }}</span>
                            <span class="phone">{{ reservation.phone }}</span>
                          </div>
                        </td>
                        <td>{{ formatDate(reservation.date) }}</td>
                        <td>{{ formatTime(reservation.time) }}</td>
                        <td>{{ reservation.guests }}</td>
                        <td>{{ reservation.table }}</td>
                        <td>
                          <span class="status-badge" :class="reservation.status">
                            {{ reservation.status }}
                          </span>
                        </td>
                        <td>
                          <span class="verified-badge" :class="{ verified: reservation.verified }">
                            <i
                              :class="
                                reservation.verified ? 'bi bi-check-circle-fill' : 'bi bi-x-circle'
                              "
                            ></i>
                            {{ reservation.verified ? 'Yes' : 'No' }}
                          </span>
                        </td>
                      </tr>
                      <tr v-if="paginatedResults.length === 0">
                        <td colspan="8" class="empty-message">
                          <i class="bi bi-inbox"></i>
                          No reservations found
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <!-- Pagination -->
                <div v-if="totalPages > 1" class="pagination">
                  <button
                    @click="currentPage--"
                    :disabled="currentPage === 1"
                    class="btn-pagination"
                  >
                    <i class="bi bi-chevron-left"></i>
                    Previous
                  </button>
                  <span class="page-info"> Page {{ currentPage }} of {{ totalPages }} </span>
                  <button
                    @click="currentPage++"
                    :disabled="currentPage === totalPages"
                    class="btn-pagination"
                  >
                    Next
                    <i class="bi bi-chevron-right"></i>
                  </button>
                </div>
              </div>
            </div>
          </section>

          <!-- Charts Row -->
          <section class="charts-section">
            <div class="row g-4">
              <!-- Status Distribution -->
              <div class="col-lg-4">
                <div class="chart-card">
                  <div class="chart-header">
                    <h3 class="chart-title">Reservation Status</h3>
                  </div>
                  <div class="chart-body">
                    <div class="donut-chart">
                      <svg viewBox="0 0 100 100" class="donut">
                        <circle
                          v-for="(segment, index) in statusSegments"
                          :key="segment.label"
                          cx="50"
                          cy="50"
                          r="40"
                          fill="none"
                          :stroke="segment.color"
                          stroke-width="15"
                          :stroke-dasharray="segment.dashArray"
                          :stroke-dashoffset="segment.dashOffset"
                          :style="{ animationDelay: `${index * 0.2}s` }"
                        />
                      </svg>
                      <div class="donut-center">
                        <span class="donut-total">{{ filteredReservations.length }}</span>
                        <span class="donut-label">Total</span>
                      </div>
                    </div>
                    <div class="chart-legend">
                      <div
                        v-for="segment in statusSegments"
                        :key="segment.label"
                        class="legend-item"
                      >
                        <span class="legend-dot" :style="{ background: segment.color }"></span>
                        <span class="legend-label">{{ segment.label }}</span>
                        <span class="legend-value">{{ segment.count }}</span>
                        <span class="legend-percent">{{ segment.percent }}%</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Reservations by Date -->
              <div class="col-lg-8">
                <div class="chart-card">
                  <div class="chart-header">
                    <h3 class="chart-title">Reservations Over Time</h3>
                  </div>
                  <div class="chart-body">
                    <div class="bar-chart">
                      <div v-for="day in reservationsByDate" :key="day.date" class="bar-container">
                        <div
                          class="bar"
                          :style="{ height: `${day.percent}%` }"
                          :title="`${day.count} reservations`"
                        >
                          <span class="bar-value">{{ day.count }}</span>
                        </div>
                        <span class="bar-label">{{ day.label }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </section>

          <!-- Second Charts Row -->
          <section class="charts-section">
            <div class="row g-4">
              <!-- Guests Distribution -->
              <div class="col-lg-6">
                <div class="chart-card">
                  <div class="chart-header">
                    <h3 class="chart-title">Guests Distribution</h3>
                  </div>
                  <div class="chart-body">
                    <div class="guests-chart">
                      <div v-for="group in guestsDistribution" :key="group.range" class="guest-bar">
                        <div class="guest-bar-wrapper">
                          <div class="guest-bar-fill" :style="{ width: `${group.percent}%` }"></div>
                        </div>
                        <span class="guest-range">{{ group.range }}</span>
                        <span class="guest-count">{{ group.count }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Peak Hours -->
              <div class="col-lg-6">
                <div class="chart-card">
                  <div class="chart-header">
                    <h3 class="chart-title">Peak Hours</h3>
                  </div>
                  <div class="chart-body">
                    <div class="timeline-chart">
                      <div v-for="hour in peakHours" :key="hour.time" class="timeline-row">
                        <span class="timeline-time">{{ hour.time }}</span>
                        <div class="timeline-bar-wrapper">
                          <div class="timeline-bar" :style="{ width: `${hour.percent}%` }"></div>
                        </div>
                        <span class="timeline-count">{{ hour.count }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </section>

          <!-- Table Utilization -->
          <section class="charts-section">
            <div class="row g-4">
              <div class="col-12">
                <div class="chart-card">
                  <div class="chart-header">
                    <h3 class="chart-title">Table Utilization</h3>
                  </div>
                  <div class="chart-body">
                    <div class="table-grid">
                      <div
                        v-for="table in tableUtilization"
                        :key="table.number"
                        class="table-card"
                        :class="{ occupied: table.reserved > 0 }"
                      >
                        <div class="table-icon">
                          <i class="bi bi-inboxes"></i>
                        </div>
                        <div class="table-info">
                          <span class="table-number">Table {{ table.number }}</span>
                          <span class="table-capacity">{{ table.capacity }} seats</span>
                        </div>
                        <div class="table-status">
                          <span class="status-count">{{ table.reserved }}</span>
                          <span class="status-label">reserved</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </section>

          <!-- Recent Activity -->
          <section class="activity-section">
            <div class="row g-4">
              <div class="col-lg-6">
                <div class="chart-card">
                  <div class="chart-header">
                    <h3 class="chart-title">Recent Reservations</h3>
                  </div>
                  <div class="chart-body">
                    <div class="activity-list">
                      <div
                        v-for="reservation in recentReservations"
                        :key="reservation.id"
                        class="activity-item"
                      >
                        <div class="activity-avatar">
                          <i class="bi bi-person"></i>
                        </div>
                        <div class="activity-details">
                          <span class="activity-name">{{ reservation.name }}</span>
                          <span class="activity-meta">
                            {{ reservation.guests }} guests · {{ formatDate(reservation.date) }}
                          </span>
                        </div>
                        <div class="activity-status" :class="reservation.status">
                          {{ reservation.status }}
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-lg-6">
                <div class="chart-card">
                  <div class="chart-header">
                    <h3 class="chart-title">Quick Insights</h3>
                  </div>
                  <div class="chart-body">
                    <div class="insights-list">
                      <div class="insight-item">
                        <div class="insight-icon success">
                          <i class="bi bi-calendar-check"></i>
                        </div>
                        <div class="insight-content">
                          <span class="insight-label">Average Daily Reservations</span>
                          <span class="insight-value">{{ averageDailyReservations }}</span>
                        </div>
                      </div>
                      <div class="insight-item">
                        <div class="insight-icon warning">
                          <i class="bi bi-people"></i>
                        </div>
                        <div class="insight-content">
                          <span class="insight-label">Average Party Size</span>
                          <span class="insight-value">{{ averagePartySize }} guests</span>
                        </div>
                      </div>
                      <div class="insight-item">
                        <div class="insight-icon info">
                          <i class="bi bi-clock-history"></i>
                        </div>
                        <div class="insight-content">
                          <span class="insight-label">Most Popular Time</span>
                          <span class="insight-value">{{ mostPopularTime }}</span>
                        </div>
                      </div>
                      <div class="insight-item">
                        <div class="insight-icon gold">
                          <i class="bi bi-star"></i>
                        </div>
                        <div class="insight-content">
                          <span class="insight-label">Confirmation Rate</span>
                          <span class="insight-value">{{ confirmationRate }}%</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </section>
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useReservationStore } from '../stores/reservations'
import { useAuthStore } from '../stores/auth'
import Navbar from '../components/Navbar.vue'

const store = useReservationStore()
const authStore = useAuthStore()
const timeFilter = ref('all')
const accessDenied = ref(false)

// Search and filter state
const searchQuery = ref('')
const statusFilter = ref('')
const dateFilter = ref('')
const tableFilter = ref('')
const currentPage = ref(1)
const itemsPerPage = 10
const sortField = ref('date')
const sortDirection = ref('desc')

const tableOptions = computed(() => store.tableNames || [])

// Check access permission - only admin and staff can access
const canAccess = computed(() => authStore.canAccessConfirmation)

// Search results computed
const searchResults = computed(() => {
  let results = filteredReservations.value

  // Filter by search query
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    results = results.filter(
      (r) =>
        r.name.toLowerCase().includes(query) ||
        r.email.toLowerCase().includes(query) ||
        r.phone.toLowerCase().includes(query) ||
        (r.company && r.company.toLowerCase().includes(query)),
    )
  }

  // Filter by status
  if (statusFilter.value) {
    results = results.filter((r) => r.status === statusFilter.value)
  }

  // Filter by date
  if (dateFilter.value) {
    results = results.filter((r) => r.date === dateFilter.value)
  }

  // Filter by table name
  if (tableFilter.value) {
    const tableQuery = tableFilter.value.toString().toLowerCase()
    results = results.filter((r) => (r.table || '').toString().toLowerCase() === tableQuery)
  }

  // Sort results
  results = [...results].sort((a, b) => {
    let aVal = a[sortField.value]
    let bVal = b[sortField.value]

    if (sortField.value === 'date' || sortField.value === 'time') {
      aVal = new Date(`2000-01-01 ${aVal}`).getTime()
      bVal = new Date(`2000-01-01 ${bVal}`).getTime()
    }

    if (sortDirection.value === 'asc') {
      return aVal > bVal ? 1 : -1
    } else {
      return aVal < bVal ? 1 : -1
    }
  })

  return results
})

// Pagination
const totalPages = computed(() => Math.ceil(searchResults.value.length / itemsPerPage))

const paginatedResults = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage
  const end = start + itemsPerPage
  return searchResults.value.slice(start, end)
})

// Reset page when filters change
watch([searchQuery, statusFilter, dateFilter, tableFilter], () => {
  currentPage.value = 1
})

// Sort icon computed
const sortIcon = computed(() => {
  return sortDirection.value === 'asc' ? 'bi bi-arrow-up' : 'bi bi-arrow-down'
})

// Sort by field
const sortBy = (field) => {
  if (sortField.value === field) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortField.value = field
    sortDirection.value = 'desc'
  }
}

// Clear filters
const clearFilters = () => {
  searchQuery.value = ''
  statusFilter.value = ''
  dateFilter.value = ''
  tableFilter.value = ''
  currentPage.value = 1
}

// Fetch reservations on mount
onMounted(async () => {
  await authStore.initializeAuth()
  if (!canAccess.value) {
    accessDenied.value = true
  } else {
    store.fetchReservations()
    store.fetchTableNames()
  }
})

// Filter reservations based on time filter
const filteredReservations = computed(() => {
  const now = new Date()
  const reservations = store.reservations

  if (timeFilter.value === 'all') return reservations

  return reservations.filter((r) => {
    const reservationDate = new Date(r.date)
    if (timeFilter.value === 'today') {
      return reservationDate.toDateString() === now.toDateString()
    } else if (timeFilter.value === 'week') {
      const weekAgo = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000)
      return reservationDate >= weekAgo
    } else if (timeFilter.value === 'month') {
      const monthAgo = new Date(now.getTime() - 30 * 24 * 60 * 60 * 1000)
      return reservationDate >= monthAgo
    }
    return true
  })
})

// Summary Stats
const summaryStats = computed(() => {
  const data = filteredReservations.value
  const confirmed = data.filter((r) => r.status === 'confirmed').length
  const pending = data.filter((r) => r.status === 'pending').length
  const totalGuests = data.reduce((sum, r) => sum + r.guests, 0)

  return [
    {
      label: 'Total Reservations',
      value: data.length,
      icon: 'bi bi-calendar-event',
      color: 'gold',
      trend: '+12%',
      trendClass: 'positive',
      trendIcon: 'bi bi-arrow-up',
    },
    {
      label: 'Confirmed',
      value: confirmed,
      icon: 'bi bi-check-circle',
      color: 'success',
      trend: '+8%',
      trendClass: 'positive',
      trendIcon: 'bi bi-arrow-up',
    },
    {
      label: 'Pending',
      value: pending,
      icon: 'bi bi-hourglass-split',
      color: 'warning',
      trend: null,
    },
    {
      label: 'Total Guests',
      value: totalGuests,
      icon: 'bi bi-people',
      color: 'info',
      trend: '+15%',
      trendClass: 'positive',
      trendIcon: 'bi bi-arrow-up',
    },
  ]
})

// Status Segments for Donut Chart
const statusSegments = computed(() => {
  const data = filteredReservations.value
  const total = data.length || 1
  const confirmed = data.filter((r) => r.status === 'confirmed').length
  const pending = data.filter((r) => r.status === 'pending').length
  const cancelled = data.filter((r) => r.status === 'cancelled').length

  const circumference = 2 * Math.PI * 40
  const confirmedPct = confirmed / total
  const pendingPct = pending / total
  const cancelledPct = cancelled / total

  const segments = [
    {
      label: 'Confirmed',
      count: confirmed,
      percent: Math.round(confirmedPct * 100),
      color: '#28a745',
      dashArray: `${confirmedPct * circumference} ${circumference}`,
      dashOffset: 0,
    },
    {
      label: 'Pending',
      count: pending,
      percent: Math.round(pendingPct * 100),
      color: '#ffc107',
      dashArray: `${pendingPct * circumference} ${circumference}`,
      dashOffset: -confirmedPct * circumference,
    },
    {
      label: 'Cancelled',
      count: cancelled,
      percent: Math.round(cancelledPct * 100),
      color: '#dc3545',
      dashArray: `${cancelledPct * circumference} ${circumference}`,
      dashOffset: -(confirmedPct + pendingPct) * circumference,
    },
  ]

  return segments
})

// Reservations by Date (last 7 days)
const reservationsByDate = computed(() => {
  const days = []
  const data = filteredReservations.value

  for (let i = 6; i >= 0; i--) {
    const date = new Date()
    date.setDate(date.getDate() - i)
    const dateStr = date.toISOString().split('T')[0]
    const dayName = date.toLocaleDateString('en-US', { weekday: 'short' })

    const count = data.filter((r) => r.date === dateStr).length
    days.push({
      date: dateStr,
      label: dayName,
      count,
      percent:
        count > 0
          ? Math.min(
              100,
              (count /
                Math.max(
                  ...data.map((r) => {
                    const d = new Date(r.date)
                    return data.filter((x) => x.date === d.toISOString().split('T')[0]).length
                  }),
                )) *
                100,
            )
          : 0,
    })
  }

  const maxCount = Math.max(...days.map((d) => d.count), 1)
  days.forEach((d) => {
    d.percent = (d.count / maxCount) * 100
  })

  return days
})

// Guests Distribution
const guestsDistribution = computed(() => {
  const data = filteredReservations.value
  const ranges = [
    { range: '1-2 guests', min: 1, max: 2 },
    { range: '3-4 guests', min: 3, max: 4 },
    { range: '5-6 guests', min: 5, max: 6 },
    { range: '7-8 guests', min: 7, max: 8 },
    { range: '9+ guests', min: 9, max: 100 },
  ]

  const distribution = ranges.map((r) => ({
    range: r.range,
    count: data.filter((res) => res.guests >= r.min && res.guests <= r.max).length,
    percent: 0,
  }))

  const maxCount = Math.max(...distribution.map((d) => d.count), 1)
  distribution.forEach((d) => {
    d.percent = (d.count / maxCount) * 100
  })

  return distribution
})

// Peak Hours
const peakHours = computed(() => {
  const data = filteredReservations.value
  const hours = []

  for (let i = 11; i <= 22; i++) {
    const hourStr = `${i > 12 ? i - 12 : i}:00 ${i >= 12 ? 'PM' : 'AM'}`
    const count = data.filter((r) => {
      const reservationHour = parseInt(r.time.split(':')[0])
      return reservationHour === i
    }).length
    hours.push({
      time: hourStr,
      count,
      percent: 0,
    })
  }

  const maxCount = Math.max(...hours.map((h) => h.count), 1)
  hours.forEach((h) => {
    h.percent = (h.count / maxCount) * 100
  })

  return hours
})

// Table Utilization
const tableUtilization = computed(() => {
  const data = filteredReservations.value
  const tables = []

  for (let i = 1; i <= 10; i++) {
    const capacity = i <= 4 ? 2 : i <= 7 ? 4 : 6
    const reserved = data.filter((r) => r.table === i).length
    tables.push({
      number: i,
      capacity,
      reserved,
    })
  }

  return tables
})

// Recent Reservations
const recentReservations = computed(() => {
  return [...filteredReservations.value]
    .sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
    .slice(0, 5)
})

// Quick Insights
const averageDailyReservations = computed(() => {
  const data = filteredReservations.value
  if (data.length === 0) return 0
  return (data.length / 7).toFixed(1)
})

const averagePartySize = computed(() => {
  const data = filteredReservations.value
  if (data.length === 0) return 0
  const total = data.reduce((sum, r) => sum + r.guests, 0)
  return (total / data.length).toFixed(1)
})

const mostPopularTime = computed(() => {
  const data = filteredReservations.value
  if (data.length === 0) return 'N/A'

  const timeCounts = {}
  data.forEach((r) => {
    timeCounts[r.time] = (timeCounts[r.time] || 0) + 1
  })

  const popular = Object.entries(timeCounts).sort((a, b) => b[1] - a[1])[0]
  if (!popular) return 'N/A'

  const [hour] = popular[0].split(':')
  const h = parseInt(hour)
  return `${h > 12 ? h - 12 : h}:00 ${h >= 12 ? 'PM' : 'AM'}`
})

const confirmationRate = computed(() => {
  const data = filteredReservations.value
  if (data.length === 0) return 0
  const confirmed = data.filter((r) => r.status === 'confirmed').length
  return Math.round((confirmed / data.length) * 100)
})

// Format date
const formatDate = (dateStr) => {
  const date = new Date(dateStr)
  return date.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
  })
}

// Format time
const formatTime = (timeStr) => {
  if (!timeStr) return '-'
  const [hours, minutes] = timeStr.split(':')
  const hour = parseInt(hours)
  const ampm = hour >= 12 ? 'PM' : 'AM'
  const displayHour = hour % 12 || 12
  return `${displayHour}:${minutes} ${ampm}`
}
</script>

<style scoped>
.analytics-page {
  min-height: 100vh;
  background: var(--bg);
  color: var(--primary-dark);
  padding-top: 90px; /* offset fixed Navbar height */
  padding-bottom: 3rem;
}

/* Access Denied */
.access-denied-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
}

.access-denied-card {
  background: #ffffff;
  border: 1px solid var(--border);
  border-radius: 16px;
  padding: 2.4rem;
  text-align: center;
  max-width: 420px;
  box-shadow: var(--shadow);
}

.access-denied-card i {
  font-size: 3.2rem;
  color: var(--accent);
  margin-bottom: 1rem;
}

.access-denied-card h2 {
  font-size: 1.8rem;
  margin-bottom: 0.5rem;
}

.access-denied-card p {
  color: #5b6b86;
  margin-bottom: 0.25rem;
}

.access-note {
  font-size: 0.9rem;
  color: #9aa5bc;
  margin-bottom: 1rem;
}

.btn-home {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  background: linear-gradient(135deg, var(--accent) 0%, #ff9f43 45%, var(--primary) 100%);
  color: #0f172a;
  border: none;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  box-shadow: 0 10px 22px rgba(31, 79, 163, 0.18);
}

.btn-home:hover {
  transform: translateY(-2px);
}

/* Header */
.analytics-header {
  background: linear-gradient(180deg, rgba(31, 79, 163, 0.08) 0%, transparent 90%);
  padding: 2rem 0 1.5rem;
  border-bottom: 1px solid var(--border);
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
}

.page-title {
  font-family: 'Playfair Display', serif;
  font-size: 2rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  color: var(--primary-dark);
}

.page-title i {
  color: var(--accent);
}

.page-subtitle {
  color: #5b6b86;
  margin-top: 0.25rem;
  font-size: 0.95rem;
}

.filter-select {
  background: #ffffff;
  border: 1px solid var(--border);
  color: var(--primary-dark);
  padding: 0.55rem 1rem;
  border-radius: 10px;
  font-size: 0.95rem;
  cursor: pointer;
  box-shadow: var(--shadow);
}

.filter-select:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(31, 79, 163, 0.12);
}

.filter-select option {
  background: #ffffff;
  color: var(--primary-dark);
}

.analytics-content {
  padding-top: 1.5rem;
}

/* Summary Stats */
.summary-section {
  margin-bottom: 2rem;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1rem;
}

.stat-card {
  background: #ffffff;
  border: 1px solid var(--border);
  border-radius: 16px;
  padding: 1.25rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  box-shadow: var(--shadow);
  transition:
    transform 0.3s ease,
    box-shadow 0.3s ease,
    border-color 0.3s ease;
}

.stat-card:hover {
  transform: translateY(-3px);
  border-color: rgba(31, 79, 163, 0.2);
  box-shadow: 0 22px 44px rgba(31, 79, 163, 0.16);
}

.stat-icon {
  width: 54px;
  height: 54px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.4rem;
  color: #0f172a;
}

.stat-icon.gold {
  background: linear-gradient(135deg, var(--accent) 0%, #ffd84d 100%);
}
.stat-icon.success {
  background: linear-gradient(135deg, #7ed957 0%, #4fc26a 100%);
  color: #0c4c2c;
}
.stat-icon.warning {
  background: linear-gradient(135deg, #ff9f43 0%, #ff6b6b 100%);
  color: #611316;
}
.stat-icon.info {
  background: linear-gradient(135deg, #8cc4ff 0%, var(--primary) 100%);
  color: #0f172a;
}

.stat-details {
  flex: 1;
}

.stat-value {
  font-size: 1.8rem;
  font-weight: 700;
  color: var(--primary-dark);
  line-height: 1;
}

.stat-label {
  font-size: 0.9rem;
  color: #5b6b86;
  margin-top: 0.25rem;
}

.stat-trend {
  font-size: 0.8rem;
  margin-top: 0.4rem;
  display: flex;
  align-items: center;
  gap: 0.3rem;
}

.stat-trend.positive {
  color: #28a745;
}
.stat-trend.negative {
  color: #dc3545;
}

/* Cards */
.charts-section {
  margin-bottom: 2rem;
}

.chart-card {
  background: #ffffff;
  border: 1px solid var(--border);
  border-radius: 16px;
  overflow: hidden;
  height: 100%;
  box-shadow: var(--shadow);
}

.chart-header {
  padding: 1.2rem 1.4rem;
  border-bottom: 1px solid var(--border);
}

.chart-title {
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--primary-dark);
  margin: 0;
}

.chart-body {
  padding: 1.4rem;
}

/* Donut */
.donut-chart {
  position: relative;
  width: 180px;
  height: 180px;
  margin: 0 auto 1.5rem;
}
.donut {
  transform: rotate(-90deg);
}
.donut circle {
  animation: drawCircle 1s ease-out forwards;
  stroke-linecap: round;
}
@keyframes drawCircle {
  from {
    stroke-dasharray: 0 251.2;
  }
}

.donut-center {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  text-align: center;
}
.donut-total {
  display: block;
  font-size: 2rem;
  font-weight: 800;
  color: var(--primary-dark);
  line-height: 1;
}
.donut-label {
  font-size: 0.8rem;
  color: #5b6b86;
}

.chart-legend {
  display: flex;
  flex-direction: column;
  gap: 0.7rem;
}
.legend-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.legend-dot {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  flex-shrink: 0;
}
.legend-label {
  flex: 1;
  font-size: 0.9rem;
  color: var(--primary-dark);
}
.legend-value {
  font-weight: 700;
  color: var(--primary-dark);
}
.legend-percent {
  font-size: 0.8rem;
  color: #5b6b86;
  width: 40px;
  text-align: right;
}

/* Bar Chart */
.bar-chart {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  height: 200px;
  gap: 0.5rem;
}
.bar-container {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  height: 100%;
}
.bar {
  width: 100%;
  max-width: 42px;
  background: linear-gradient(180deg, var(--primary) 0%, rgba(31, 79, 163, 0.45) 100%);
  border-radius: 8px 8px 0 0;
  min-height: 6px;
  position: relative;
  transition: all 0.3s ease;
  animation: growBar 0.8s ease-out forwards;
}
@keyframes growBar {
  from {
    height: 0 !important;
  }
}
.bar:hover {
  filter: brightness(1.05);
  box-shadow: 0 14px 24px rgba(31, 79, 163, 0.22);
}
.bar-value {
  position: absolute;
  top: -22px;
  left: 50%;
  transform: translateX(-50%);
  font-size: 0.8rem;
  font-weight: 700;
  color: var(--primary-dark);
}
.bar-label {
  font-size: 0.75rem;
  color: #5b6b86;
  margin-top: 0.5rem;
}

/* Guests */
.guests-chart {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
.guest-bar {
  display: flex;
  align-items: center;
  gap: 1rem;
}
.guest-bar-wrapper {
  flex: 1;
  height: 22px;
  background: #f2f5ff;
  border-radius: 12px;
  overflow: hidden;
}
.guest-bar-fill {
  height: 100%;
  background: linear-gradient(90deg, var(--accent) 0%, var(--primary) 100%);
  border-radius: 12px;
  transition: width 0.8s ease-out;
}
.guest-range {
  width: 82px;
  font-size: 0.85rem;
  color: var(--primary-dark);
}
.guest-count {
  width: 34px;
  text-align: right;
  font-weight: 700;
  color: var(--primary-dark);
}

/* Timeline */
.timeline-chart {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}
.timeline-row {
  display: flex;
  align-items: center;
  gap: 1rem;
}
.timeline-time {
  width: 70px;
  font-size: 0.85rem;
  color: #5b6b86;
}
.timeline-bar-wrapper {
  flex: 1;
  height: 16px;
  background: #eef2fb;
  border-radius: 10px;
  overflow: hidden;
}
.timeline-bar {
  height: 100%;
  background: linear-gradient(90deg, rgba(31, 79, 163, 0.15) 0%, var(--primary) 100%);
  border-radius: 10px;
  transition: width 0.8s ease-out;
}
.timeline-count {
  width: 28px;
  text-align: right;
  font-size: 0.9rem;
  font-weight: 700;
  color: var(--primary-dark);
}

/* Table grid */
.table-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  gap: 1rem;
}
.table-card {
  background: #f6f8ff;
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 1rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.45rem;
  transition: all 0.3s ease;
}
.table-card.occupied {
  border-color: rgba(31, 79, 163, 0.3);
  background: #eaf1ff;
}
.table-icon {
  font-size: 1.5rem;
  color: #5b6b86;
}
.table-card.occupied .table-icon {
  color: var(--primary);
}
.table-number {
  font-weight: 700;
  color: var(--primary-dark);
  font-size: 0.95rem;
}
.table-capacity {
  font-size: 0.8rem;
  color: #5b6b86;
}
.status-count {
  display: block;
  font-size: 1.2rem;
  font-weight: 800;
  color: var(--primary-dark);
}
.status-label {
  font-size: 0.7rem;
  color: #5b6b86;
  text-transform: uppercase;
}

/* Activity */
.activity-section {
  margin-bottom: 2rem;
}
.activity-list {
  display: flex;
  flex-direction: column;
  gap: 0.9rem;
}
.activity-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.75rem;
  background: #f6f8ff;
  border-radius: 10px;
  border: 1px solid var(--border);
  transition:
    transform 0.2s ease,
    box-shadow 0.2s ease;
}
.activity-item:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 24px rgba(31, 79, 163, 0.12);
}
.activity-avatar {
  width: 40px;
  height: 40px;
  background: linear-gradient(135deg, var(--primary) 0%, #8cc4ff 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 1rem;
}
.activity-name {
  display: block;
  font-weight: 600;
  color: var(--primary-dark);
  font-size: 0.95rem;
}
.activity-meta {
  font-size: 0.85rem;
  color: #5b6b86;
}
.activity-status {
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  padding: 0.25rem 0.55rem;
  border-radius: 6px;
}
.activity-status.confirmed {
  background: rgba(126, 217, 87, 0.2);
  color: #1f7a2f;
}
.activity-status.pending {
  background: rgba(246, 196, 0, 0.22);
  color: #b87400;
}
.activity-status.cancelled {
  background: rgba(255, 107, 107, 0.2);
  color: #c92c3a;
}

/* Insights */
.insights-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
.insight-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: #f6f8ff;
  border-radius: 12px;
  border: 1px solid var(--border);
}
.insight-icon {
  width: 46px;
  height: 46px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.3rem;
  color: #0f172a;
}
.insight-icon.success {
  background: rgba(126, 217, 87, 0.3);
}
.insight-icon.warning {
  background: rgba(246, 196, 0, 0.3);
}
.insight-icon.info {
  background: rgba(140, 196, 255, 0.35);
}
.insight-icon.gold {
  background: rgba(255, 214, 77, 0.35);
}
.insight-label {
  display: block;
  font-size: 0.9rem;
  color: #5b6b86;
}
.insight-value {
  font-size: 1.35rem;
  font-weight: 800;
  color: var(--primary-dark);
}

/* Search */
.search-section {
  margin-top: 2rem;
}
.search-filters {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  margin-bottom: 1.2rem;
  align-items: center;
}
.search-input-group {
  flex: 1;
  min-width: 280px;
  position: relative;
}
.search-input-group i {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: #8a97af;
}
.search-input {
  width: 100%;
  padding: 0.75rem 1rem 0.75rem 2.75rem;
  background: #ffffff;
  border: 1px solid var(--border);
  border-radius: 10px;
  color: var(--primary-dark);
  font-size: 0.95rem;
  transition:
    border-color 0.2s,
    box-shadow 0.2s;
}
.search-input:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(31, 79, 163, 0.12);
}
.filter-group {
  display: flex;
  gap: 0.75rem;
  align-items: center;
}
.filter-select,
.date-input {
  padding: 0.75rem 1rem;
  background: #ffffff;
  border: 1px solid var(--border);
  border-radius: 10px;
  color: var(--primary-dark);
  font-size: 0.95rem;
  cursor: pointer;
  transition: border-color 0.2s;
}
.filter-select:focus,
.date-input:focus {
  outline: none;
  border-color: var(--primary);
}
.date-input {
  color-scheme: light;
}
.btn-clear {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  background: #f6f8ff;
  border: 1px solid var(--border);
  border-radius: 10px;
  color: var(--primary-dark);
  cursor: pointer;
}
.btn-clear:hover {
  background: #eaf1ff;
}
.results-info {
  margin-bottom: 1rem;
  color: #5b6b86;
  font-size: 0.95rem;
}

/* Data Table */
.table-responsive {
  overflow-x: auto;
}
.data-table {
  width: 100%;
  border-collapse: collapse;
  background: #ffffff;
  border-radius: 12px;
  overflow: hidden;
  border: 1px solid var(--border);
}
.data-table th,
.data-table td {
  padding: 0.95rem 1rem;
  text-align: left;
  border-bottom: 1px solid var(--border);
}
.data-table th {
  background: #f6f8ff;
  color: var(--primary-dark);
  font-weight: 700;
  font-size: 0.85rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  cursor: default;
}
.data-table th.sortable {
  cursor: pointer;
}
.data-table th.sortable:hover {
  background: #eaf1ff;
}
.data-table td {
  color: #42506a;
  font-size: 0.95rem;
}
.data-table tbody tr:hover {
  background: #f9fbff;
}
.customer-cell {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}
.customer-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--accent) 0%, #ffd84d 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #0f172a;
  font-weight: 700;
  font-size: 0.9rem;
}
.customer-name {
  color: var(--primary-dark);
  font-weight: 600;
}
.customer-company {
  color: #8a97af;
  font-size: 0.85rem;
}
.contact-cell {
  display: flex;
  flex-direction: column;
}
.contact-cell .phone {
  color: #8a97af;
  font-size: 0.85rem;
}
.status-badge {
  display: inline-block;
  padding: 0.35rem 0.75rem;
  border-radius: 20px;
  font-size: 0.78rem;
  font-weight: 700;
  text-transform: capitalize;
}
.status-badge.pending {
  background: rgba(246, 196, 0, 0.18);
  color: #b87400;
}
.status-badge.confirmed {
  background: rgba(126, 217, 87, 0.2);
  color: #1f7a2f;
}
.status-badge.cancelled {
  background: rgba(255, 107, 107, 0.2);
  color: #c92c3a;
}
.verified-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.85rem;
  color: #c92c3a;
}
.verified-badge.verified {
  color: #1f7a2f;
}
.empty-message {
  text-align: center;
  padding: 2.5rem !important;
  color: #8a97af;
}
.empty-message i {
  font-size: 2rem;
  display: block;
  margin-bottom: 0.5rem;
  color: var(--primary);
}

/* Pagination */
.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 1rem;
  margin-top: 1.25rem;
  padding-top: 1.25rem;
  border-top: 1px solid var(--border);
}
.page-info {
  color: #5b6b86;
  font-size: 0.95rem;
}
.btn-pagination {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.55rem 1rem;
  background: #f6f8ff;
  border: 1px solid var(--border);
  border-radius: 8px;
  color: var(--primary-dark);
  cursor: pointer;
  transition: all 0.2s;
}
.btn-pagination:hover:not(:disabled) {
  background: #eaf1ff;
}
.btn-pagination:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

/* Responsive */
@media (max-width: 768px) {
  .page-title {
    font-size: 1.6rem;
  }
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  .bar-chart {
    height: 150px;
  }
  .table-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  .search-filters {
    flex-direction: column;
    align-items: stretch;
  }
  .filter-group {
    flex-wrap: wrap;
  }
}
</style>
