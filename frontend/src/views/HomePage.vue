<template>
  <div class="home-page">
    <Navbar />

    <div class="home-page-content">
      <!-- Header -->
      <header class="home-header">
        <!-- Hero Section -->
        <section class="hero-section">
          <div class="hero-content">
            <h1 class="hero-title">
              <span class="title-line">Halal Bihalal</span>
              <span class="title-line"
                ><img src="/connected-in-harmony.png" alt="Resonanz Logo" class="logo-icon"
              /></span>
            </h1>
            <p class="hero-subtitle">
              bersama
              <span><img src="/new-iforte.png" alt="Resonanz Logo" class="logo-icon" /></span>
            </p>
            <div class="hero-line"></div>
          </div>
        </section>
        <div class="container">
          <div class="header-content">
            <div class="header-text">
              <h1 class="page-title">
                <i class="bi bi-house-heart"></i>
                Dashboard
              </h1>
              <p class="page-subtitle">Overview and reservations</p>
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

      <main class="home-content">
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
                  Reservations
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
                      placeholder="Search by name, email, or table..."
                      class="search-input"
                    />
                  </div>
                  <div class="filter-group">
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
                    <select v-model="salesConnectionFilter" class="filter-select" placeholder="All Sales">
                      <option value="">All Sales</option>
                      <option v-for="sales in salesConnectionOptions" :key="sales" :value="sales">
                        {{ sales }}
                      </option>
                    </select>
                    <button @click="clearFilters" class="btn-clear">
                      <i class="bi bi-x-circle"></i>
                      Clear
                    </button>
                  </div>
                </div>

                <!-- Results Count -->
                <div class="results-info">
                  <span>
                    Showing {{ tableResults.length }} of {{ tablePagination.total }} reservations
                  </span>
                  <span v-if="tableLoading" class="loading-text">Loading...</span>
                  <span v-if="tableError" class="error-text">{{ tableError }}</span>
                  <div class="per-page">
                    <select
                      id="per-page"
                      v-model.number="itemsPerPage"
                      class="filter-select compact"
                    >
                      <option :value="5">5</option>
                      <option :value="10">10</option>
                      <option :value="20">20</option>
                      <option :value="25">25</option>
                      <option :value="50">50</option>
                      <option :value="100">100</option>
                    </select>
                    <label for="per-page">/page</label>
                  </div>
                </div>

                <!-- Data Table -->
                <div class="table-responsive">
                  <table class="data-table">
                    <thead>
                      <tr>
                        <th @click="sortBy('name')" class="sortable">
                          Guest
                          <i v-if="sortField === 'name'" :class="sortIcon"></i>
                        </th>
                        <th>Table</th>
                        <th>Sales</th>
                        <th>Status</th>
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
                            </div>
                          </div>
                        </td>
                        <td>{{ reservation.seatCode }}</td>
                        <td>{{ reservation.salesConnection || '-' }}</td>
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
                        <td colspan="6" class="empty-message">
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
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useReservationStore } from '../stores/reservations'
import Navbar from '../components/Navbar.vue'

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'

const store = useReservationStore()
const timeFilter = ref('all')

// Search and filter state
const searchQuery = ref('')
const statusFilter = ref('')
const verifiedFilter = ref('')
const tableFilter = ref('')
const salesConnectionFilter = ref('')
const currentPage = ref(1)
const itemsPerPage = ref(20)
const sortField = ref('name')
const sortDirection = ref('desc')

const tableOptions = computed(() => store.tableNames || [])
const salesConnectionOptions = ref([])

const fetchSalesConnections = async () => {
  try {
    const response = await fetch(`${API_URL}/reservations/sales-connections`)
    const data = await response.json()
    if (data.success) {
      salesConnectionOptions.value = data.data || []
    }
  } catch (err) {
    console.error('Failed to load sales connections:', err)
  }
}

// Server-backed table data
const tableData = ref([])
const tablePagination = ref({
  page: 1,
  perPage: itemsPerPage.value,
  total: 0,
  totalPages: 1,
})
const tableLoading = ref(false)
const tableError = ref(null)

const tableResults = computed(() => {
  let results = tableData.value

  // Time filter (client-side)
  if (timeFilter.value !== 'all') {
    const now = new Date()
    results = results.filter((r) => {
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
  }

  // Table filter (client-side exact match)
  if (tableFilter.value) {
    const tableQuery = tableFilter.value.toString().toLowerCase()
    results = results.filter((r) => (r.seatCode || '').toString().toLowerCase() === tableQuery)
  }

  // Sales connection filter
  if (salesConnectionFilter.value) {
    const salesQuery = salesConnectionFilter.value.toString().toLowerCase()
    results = results.filter((r) => (r.salesConnection || '').toString().toLowerCase() === salesQuery)
  }

  // Sort results
  results = [...results].sort((a, b) => {
    let aVal = a[sortField.value]
    let bVal = b[sortField.value]

    if (sortDirection.value === 'asc') {
      return aVal > bVal ? 1 : -1
    } else {
      return aVal < bVal ? 1 : -1
    }
  })

  return results
})

const totalPages = computed(() => {
  if (tablePagination.value.totalPages) return tablePagination.value.totalPages
  const total = tablePagination.value.total || 0
  return Math.max(1, Math.ceil(total / itemsPerPage.value))
})
const paginatedResults = computed(() => tableResults.value)

const fetchTableData = async () => {
  tableLoading.value = true
  tableError.value = null

  const params = new URLSearchParams()
  params.set('page', currentPage.value.toString())
  params.set('limit', itemsPerPage.value.toString())
  if (statusFilter.value) params.set('status', statusFilter.value)
  if (verifiedFilter.value !== '') params.set('verified', verifiedFilter.value)
  if (searchQuery.value) params.set('search', searchQuery.value)
  if (tableFilter.value) params.set('table', tableFilter.value)
  if (salesConnectionFilter.value) params.set('salesConnection', salesConnectionFilter.value)

  try {
    const response = await fetch(`${API_URL}/reservations?${params.toString()}`)
    const data = await response.json()

    if (data.success) {
      tableData.value = data.data || []
      tablePagination.value = data.pagination || {
        page: currentPage.value,
        perPage: itemsPerPage.value,
        total: data.data?.length || 0,
        totalPages: 1,
      }
      if (data.pagination?.page) {
        currentPage.value = data.pagination.page
      }
    } else {
      tableError.value = data.error || 'Failed to load reservations'
    }
  } catch (err) {
    tableError.value = 'Network error: Unable to connect to server'
    console.error('Error loading reservations:', err)
  } finally {
    tableLoading.value = false
  }
}

// Reset page and refetch when filters change
watch([searchQuery, statusFilter, tableFilter, verifiedFilter, salesConnectionFilter], () => {
  currentPage.value = 1
  fetchTableData()
})

// Refetch when page changes
watch(currentPage, () => {
  fetchTableData()
})

// Refetch when per-page changes
watch(itemsPerPage, () => {
  currentPage.value = 1
  fetchTableData()
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
  verifiedFilter.value = ''
  tableFilter.value = ''
  salesConnectionFilter.value = ''
  currentPage.value = 1
  fetchTableData()
}

// Fetch reservations on mount
onMounted(async () => {
  store.fetchReservations()
  store.fetchTableNames()
  await fetchTableData()
  fetchSummary()
  fetchSalesConnections()
})

// Reset page on client-only filters
watch([timeFilter], () => {
  currentPage.value = 1
})

// Summary Stats
const summaryStatsData = ref({
  totalReservations: 0,
  confirmed: 0,
  pending: 0,
  totalGuests: 0,
})

const fetchSummary = async () => {
  try {
    const res = await fetch(`${API_URL}/reservations/summary`)
    const data = await res.json()
    if (data.success && data.data) {
      summaryStatsData.value = {
        totalReservations: data.data.totalReservations ?? 0,
        confirmed: data.data.confirmed ?? 0,
        verified: data.data.verified ?? 0,
        totalGuests: data.data.totalGuests ?? 0,
      }
    }
  } catch (err) {
    console.error('Failed to load summary', err)
  }
}

const summaryStats = computed(() => {
  return [
    {
      label: 'Total Guests',
      value: summaryStatsData.value.confirmed,
      icon: 'bi bi-people',
      color: 'info',
      trend: null,
    },
    {
      label: 'Verified',
      value: summaryStatsData.value.verified,
      icon: 'bi bi-check-circle',
      color: 'success',
      trend: null,
    },
  ]
})
</script>

<style scoped>
.home-page {
  background: var(--bg);
  color: var(--primary-dark);
  padding-top: 90px;
  padding-bottom: 3rem;
}

/* Header */
.home-header {
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

/* Hero Section */
.hero-section {
  text-align: center;
  margin-bottom: 4rem;
  animation: fadeInUp 1s ease-out;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(40px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.hero-title {
  font-family: 'Playfair Display', serif;
  font-size: clamp(2.5rem, 6vw, 4.5rem);
  font-weight: 700;
  line-height: 1.1;
  margin-bottom: 1rem;
  color: var(--primary-dark);
}

.title-line {
  display: block;
  opacity: 0;
  animation: slideUp 0.8s ease-out forwards;
}

.title-line img {
  width: 45%;
  height: auto;
}

.title-line:nth-child(1) {
  animation-delay: 0.2s;
}
.title-line:nth-child(2) {
  animation-delay: 0.4s;
}
.title-line:nth-child(3) {
  animation-delay: 0.6s;
}

.title-line.gold {
  background: linear-gradient(135deg, #d4af37 0%, #f4e5c2 50%, #d4af37 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  background-size: 200% 200%;
  animation:
    slideUp 0.8s ease-out 0.4s forwards,
    shimmer 3s ease-in-out infinite;
  background: linear-gradient(135deg, var(--accent) 0%, #ff9f43 50%, var(--primary) 100%);
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes shimmer {
  0%,
  100% {
    background-position: 0% 50%;
  }
  50% {
    background-position: 100% 50%;
  }
}

.hero-subtitle {
  font-size: 1.2rem;
  color: #5b6b86;
  letter-spacing: 2px;
  text-transform: uppercase;
  margin-bottom: 2rem;
  opacity: 0;
  animation: fadeIn 1s ease-out 0.8s forwards;
}

.hero-subtitle span {
  height: 30px;
}

.hero-subtitle span img {
  width: 10%;
}

@keyframes fadeIn {
  to {
    opacity: 1;
  }
}

.hero-line {
  width: 100px;
  height: 2px;
  background: linear-gradient(90deg, transparent, var(--primary), transparent);
  margin: 0 auto;
  opacity: 0;
  animation: expandLine 1s ease-out 1s forwards;
}

@keyframes expandLine {
  from {
    width: 0;
    opacity: 0;
  }
  to {
    width: 100px;
    opacity: 1;
  }
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
  width: 100%;
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

.home-content {
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

/* Search */
.search-section {
  margin-top: 2rem;
  margin-bottom: 2rem;
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
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
}

.loading-text {
  color: var(--primary);
}

.error-text {
  color: #dc3545;
}

.per-page {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.filter-select.compact {
  padding: 0.4rem 0.6rem;
  min-width: 80px;
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
  text-align: center !important;
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
  .search-filters {
    flex-direction: column;
    align-items: stretch;
  }
  .filter-group {
    flex-wrap: wrap;
  }
}
</style>
