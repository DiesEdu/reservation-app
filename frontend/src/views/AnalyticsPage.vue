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
                      placeholder="Search by name, email, or table..."
                      class="search-input"
                    />
                  </div>
                  <div class="filter-group">
                    <select v-model="verifiedFilter" class="filter-select">
                      <option value="">All Verified</option>
                      <option value="1">Verified</option>
                      <option value="0">Not Verified</option>
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
                    <label for="per-page">Per page:</label>
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
                  </div>
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
                        <th>Table</th>
                        <th>Verified</th>
                        <th>Actions</th>
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
                        <td>{{ reservation.table }}</td>
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
                        <td>
                          <div class="action-buttons">
                            <button
                              @click="showPopupManualVerification(reservation.id)"
                              class="btn btn-view"
                              title="View details"
                            >
                              <i class="bi bi-check-circle"></i>
                            </button>
                          </div>
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
      <!-- Manual Verification Modal -->
      <transition name="modal-fade">
        <div
          v-if="verificationModalVisible && selectedReservation"
          class="verification-modal-overlay"
          role="dialog"
          aria-modal="true"
          @click.self="closeVerificationModal"
        >
          <transition name="modal-rise">
            <div class="verification-modal" v-if="verificationModalVisible">
              <button class="modal-close" @click="closeVerificationModal" aria-label="Close dialog">
                <i class="bi bi-x-lg"></i>
              </button>

              <div class="modal-header">
                <div>
                  <p class="modal-eyebrow">Manual Verification</p>
                  <h3>{{ selectedReservation.name }}</h3>
                  <p class="modal-subtitle">{{ selectedReservation.company || 'No company' }}</p>
                </div>
                <div class="modal-badge" :class="{ verified: verificationDetails?.verified }">
                  <i
                    :class="verificationDetails?.verified ? 'bi bi-check-circle' : 'bi bi-hourglass'"
                  ></i>
                  {{ verificationDetails?.verified ? 'Verified' : 'Not Verified' }}
                </div>
              </div>

              <div class="modal-body">
                <div class="modal-info-grid">
                  <div>
                    <p class="label">Table</p>
                    <p class="value">{{ selectedReservation.table || '-' }}</p>
                  </div>
                  <div>
                    <p class="label">QR Code</p>
                    <p class="value code">{{ selectedReservation.qrCode }}</p>
                  </div>
                  <div>
                    <p class="label">Status</p>
                    <p class="value status">
                      {{ verificationDetails?.status || selectedReservation.status }}
                    </p>
                  </div>
                  <div>
                    <p class="label">Verified At</p>
                    <p class="value">
                      {{ verificationDetails?.verifiedAt || 'Not yet verified' }}
                    </p>
                  </div>
                </div>

                <div v-if="verificationError" class="modal-alert error">
                  <i class="bi bi-exclamation-triangle"></i>
                  <span>{{ verificationError }}</span>
                </div>
                <div v-if="verificationSuccess" class="modal-alert success">
                  <i class="bi bi-check-circle-fill"></i>
                  <span>{{ verificationSuccess }}</span>
                </div>
              </div>

              <div class="modal-actions">
                <button
                  v-if="!verificationDetails?.verified"
                  class="btn-outline"
                  @click="checkVerificationStatus(selectedReservation.qrCode)"
                  :disabled="verificationLoading"
                >
                  <i v-if="verificationLoading" class="bi bi-arrow-repeat spinner"></i>
                  <i v-else class="bi bi-arrow-clockwise"></i>
                  <span>Recheck</span>
                </button>
                <button
                  v-if="!verificationDetails?.verified"
                  class="btn-primary"
                  @click="async () => { await confirmManualVerification(); printTicket(); }"
                  :disabled="verificationLoading"
                >
                  <i v-if="verificationLoading" class="bi bi-hourglass-split spinner"></i>
                  <i v-else class="bi bi-check-circle"></i>
                  <span>Verify</span>
                </button>
                <button v-if="verificationDetails?.verified" class="btn-secondary" @click="printTicket" :disabled="!selectedReservation">
                  <i class="bi bi-printer"></i>
                  <span>Print Ticket</span>
                </button>
              </div>
            </div>
          </transition>
        </div>
      </transition>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useReservationStore } from '../stores/reservations'
import { useAuthStore } from '../stores/auth'
import QRCode from 'qrcode'
import Navbar from '../components/Navbar.vue'
import granville from '@/assets/fonts/Granville.otf';
import montserrat from '@/assets/fonts/Montserrat-VariableFont_wght.ttf';
import cinzel from '@/assets/fonts/cinzel/Cinzel-Regular.otf';

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'

const store = useReservationStore()
const authStore = useAuthStore()
const timeFilter = ref('all')
const accessDenied = ref(false)

// Search and filter state
const searchQuery = ref('')
const statusFilter = ref('')
const verifiedFilter = ref('')
const tableFilter = ref('')
const currentPage = ref(1)
const itemsPerPage = ref(20)
const sortField = ref('name')
const sortDirection = ref('desc')

const tableOptions = computed(() => store.tableNames || [])

// Check access permission - only admin and staff can access
const canAccess = computed(() => authStore.canAccessConfirmation)

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

// Manual verification modal state
const verificationModalVisible = ref(false)
const selectedReservation = ref(null)
const verificationDetails = ref(null)
const verificationLoading = ref(false)
const verificationError = ref('')
const verificationSuccess = ref('')
const qrCodeDataUrl = ref('')

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
    results = results.filter((r) => (r.table || '').toString().toLowerCase() === tableQuery)
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

// Save search to SSE after table data is loaded
// const saveSearchAfterFetch = () => {
//   if (searchQuery.value && searchQuery.value.length >= 2) {
//     saveSearchForSSE(searchQuery.value)
//   }
// }

const saveSearchForSSE = async (query, verified = false) => {
  if (!query || query.length < 2) return

  const userEmail = authStore.user?.email || ''
  console.log('Saving search to SSE:', { query, userEmail, verified, API_URL: `${API_URL}/save-search` })

  try {
    const response = await fetch(`${API_URL}/save-search`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ search: query, email: userEmail, verified: verified })
    })
    const result = await response.json()
    console.log('SSE save response:', result)
  } catch (err) {
    console.error('Failed to save search for SSE:', err)
  }
}

// Reset page and refetch when filters change
watch([searchQuery, statusFilter, tableFilter, verifiedFilter], () => {
  currentPage.value = 1
  fetchTableData()
})

// Watch search query to save to SSE after debounce
let searchDebounceTimer = null
watch(searchQuery, (newVal) => {
  if (searchDebounceTimer) clearTimeout(searchDebounceTimer)
  if (newVal && newVal.length >= 2) {
    searchDebounceTimer = setTimeout(() => {
      saveSearchForSSE(newVal)
    }, 500)
  }
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
  currentPage.value = 1
  fetchTableData()
}

const showPopupManualVerification = async (reservationId) => {
  const found = tableData.value.find((item) => item.id === reservationId)
  if (!found) return

  selectedReservation.value = found
  verificationDetails.value = null
  verificationError.value = ''
  verificationSuccess.value = ''
  verificationModalVisible.value = true
  await checkVerificationStatus(found.qrCode)
}

const closeVerificationModal = () => {
  verificationModalVisible.value = false
  selectedReservation.value = null
  verificationDetails.value = null
  verificationError.value = ''
  verificationSuccess.value = ''
  qrCodeDataUrl.value = ''
}

const checkVerificationStatus = async (qrCode) => {
  verificationLoading.value = true
  verificationError.value = ''
  try {
    const response = await fetch(`${API_URL}/reservations/verification-status`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ qrCode }),
    })
    const result = await response.json()
    if (!response.ok || !result.success) {
      throw new Error(result.error || 'Unable to check verification status')
    }
    verificationDetails.value = result.data
  } catch (err) {
    verificationError.value = err.message || 'Unable to check verification status'
  } finally {
    verificationLoading.value = false
  }
}

const confirmManualVerification = async () => {
  if (!selectedReservation.value) return
  verificationLoading.value = true
  verificationError.value = ''
  verificationSuccess.value = ''

  try {
    const response = await fetch(`${API_URL}/reservations/verify`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        qrCode: selectedReservation.value.qrCode,
        method: 'manual_entry',
      }),
    })
    const result = await response.json()
    if (!response.ok || !result.success) {
      throw new Error(result.error || 'Failed to verify reservation')
    }

    verificationDetails.value = result.data
    verificationSuccess.value = result.message || 'Reservation verified successfully'
    updateLocalReservation(result.data)
    store.fetchReservations()

    // Save verified status to SSE events
    const guestName = selectedReservation.value.name || ''
    if (guestName) {
      await saveSearchForSSE(guestName, true)
    }
  } catch (err) {
    verificationError.value = err.message || 'Failed to verify reservation'
  } finally {
    verificationLoading.value = false
  }
}

const updateLocalReservation = (updated) => {
  tableData.value = tableData.value.map((item) =>
    item.id === updated.id
      ? { ...item, verified: updated.verified, verifiedAt: updated.verifiedAt }
      : item,
  )
  selectedReservation.value = tableData.value.find((item) => item.id === updated.id)
}

const convertToDataURL = async (url) => {
  const response = await fetch(url);
  const blob = await response.blob();
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onloadend = () => resolve(reader.result);
    reader.onerror = reject;
    reader.readAsDataURL(blob);
  });
};

const generateQRCode = async (reservation) => {
  selectedReservation.value = reservation
  const qrData =
    reservation.qrCode || `RES-${reservation.id}-${new Date(reservation.createdAt).getTime()}`

  try {
    qrCodeDataUrl.value = await QRCode.toDataURL(qrData, {
      width: 256,
      margin: 2,
      color: {
        dark: '#0a0a0a',
        light: '#ffffff',
      },
    })
  } catch (err) {
    console.error('Failed to generate QR code:', err)
    verificationError.value = 'Failed to generate QR code for print.'
  }
}

const printTicket = async () => {
  if (!selectedReservation.value) return
  const res = selectedReservation.value
  qrCodeDataUrl.value = ''
  await generateQRCode(res)

  const fontGranville = await convertToDataURL(granville);
  const fontMontserrat = await convertToDataURL(montserrat);
  const fontCinzel = await convertToDataURL(cinzel);

  const html = `
    <html>
      <head>
        <title>Reservation Ticket</title>
        <style>
          @font-face {
            font-family: 'granville';
            src: url('${fontGranville}') format('opentype');
            font-weight: normal;
            font-style: normal;
          }
          @font-face {
            font-family: 'montserrat';
            src: url('${fontMontserrat}') format('opentype');
            font-weight: normal;
            font-style: normal;
          }
          @font-face {
            font-family: 'cinzel';
            src: url('${fontCinzel}') format('opentype');
            font-weight: normal;
            font-style: normal;
          }
          @page {
            size: 50mm 30mm;
            margin: 0;
          }
          * { box-sizing: border-box; margin: 0; padding: 0; }
          body {
            font-family: 'granville', 'Arial', sans-serif;
            width: 50mm;
            height: 30mm;
            margin-top: -5px;
            margin-left: 10px;
            color: #0f172a;
            display: flex;
            align-items: center;
            justify-content: center;
          }
          .ticket {
            width: 100%;
            height: 100%;
            padding: 0.5mm 0.5mm;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
          }
          .table-num {
            font-family: 'granville', 'Arial', sans-serif;
            font-size: 40px;
            font-weight: 800;
            color: #0a0a0a;
            margin-top: -5px;
          }
          .table-label {
            font-family: 'granville', 'Arial', sans-serif;
            font-size: 10px;
            font-style: italic;
            letter-spacing: 0.5px;
            color: #333;
            margin-bottom: 0;
          }
          .name {
            font-family: 'granville', 'Arial', sans-serif;
            font-size: 14px;
            font-weight: 600;
            color: #333;
            margin-top: -5px;
            white-space: normal;
            word-wrap: break-word;
          }
          .company {
            font-family: 'granville', 'Arial', sans-serif;
            font-size: 12px;
            font-weight: 600;
            color: #333;
            white-space: normal;
            word-wrap: break-word;
          }
          .position {
            font-family: 'granville', 'Arial', sans-serif;
            font-size: 12px;
            font-weight: 600;
            color: #333;
            white-space: normal;
            word-wrap: break-word;
          }
          .logo-iforte {
            height: 12px;
            margin-bottom: 2mm;
          }
          .logo-iforte img {
            height: 100%;
            width: auto;
          }
        </style>
      </head>
      <body>
        <div class="ticket">
          <div class="table-label">Table</div>
          <div class="table-num">${res.table || '-'}</div>
          <div class="name" style="${((res.name?.length || 0) > 30 || (res.company?.length || 0) > 30 || (res.position?.length || 0) > 30) ? 'font-size: 9px;' : ''}">${res.name}</div>
          <div class="position" style="${((res.name?.length || 0) > 30 || (res.company?.length || 0) > 30 || (res.position?.length || 0) > 30) ? 'font-size: 8px;' : ''}">${res.position}</div>
          <div class="company" style="${((res.name?.length || 0) > 30 || (res.company?.length || 0) > 30 || (res.position?.length || 0) > 30) ? 'font-size: 8px;' : ''}">${res.company}</div>
        </div>
      </body>
    </html>
  `

  const iframe = document.createElement('iframe')
  iframe.style.position = 'fixed'
  iframe.style.left = '-99999px'
  iframe.style.width = '0'
  iframe.style.height = '0'
  iframe.setAttribute('aria-hidden', 'true')
  document.body.appendChild(iframe)

  const doc = iframe.contentWindow?.document
  if (!doc) {
    verificationError.value = 'Unable to initiate print.'
    iframe.remove()
    return
  }

  doc.open()
  doc.write(html)
  doc.close()

  iframe.onload = () => {
    try {
      const win = iframe.contentWindow
      win?.focus()
      win?.print()
    } catch (err) {
      console.error('Print error:', err)
      verificationError.value = 'Unable to print ticket.'
    } finally {
      setTimeout(() => iframe.remove(), 200)
    }
  }
}

// Fetch reservations on mount
onMounted(async () => {
  await authStore.initializeAuth()
  if (!canAccess.value) {
    accessDenied.value = true
  } else {
    store.fetchReservations()
    store.fetchTableNames()
    await fetchTableData()
    fetchSummary()
    fetchAnalytics()
  }
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

const analyticsData = ref({
  statusCounts: [],
  dailyCounts: [],
  guestDistribution: [],
  peakHours: [],
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

const fetchAnalytics = async () => {
  try {
    const res = await fetch(`${API_URL}/reservations/analytics`)
    const data = await res.json()
    if (data.success && data.data) {
      analyticsData.value = {
        statusCounts: data.data.statusCounts || [],
        dailyCounts: data.data.dailyCounts || [],
        guestDistribution: data.data.guestDistribution || [],
        peakHours: data.data.peakHours || [],
      }
    }
  } catch (err) {
    console.error('Failed to load analytics', err)
  }
}

const summaryStats = computed(() => {
  return [
    {
      label: 'Total Guests',
      value: summaryStatsData.value.totalGuests,
      icon: 'bi bi-people',
      color: 'gold',
      trend: null,
    },
    {
      label: 'Verified',
      value: summaryStatsData.value.verified,
      icon: 'bi bi-hourglass-split',
      color: 'success',
      trend: null,
    },
  ]
})
</script>

<style scoped>
.analytics-page {
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

/* Verification Modal */
.verification-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.55);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1200;
  padding: 1rem;
}

.verification-modal {
  background: #ffffff;
  border-radius: 16px;
  padding: 1.5rem;
  width: min(560px, 100%);
  box-shadow: 0 24px 64px rgba(0, 0, 0, 0.2);
  position: relative;
  border: 1px solid var(--border);
}

.modal-close {
  position: absolute;
  top: 0.75rem;
  right: 0.75rem;
  background: transparent;
  border: none;
  cursor: pointer;
  color: #94a3b8;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  align-items: flex-start;
  margin-bottom: 1rem;
}

.modal-eyebrow {
  text-transform: uppercase;
  letter-spacing: 0.05em;
  font-size: 0.75rem;
  color: #94a3b8;
  margin-bottom: 0.25rem;
}

.modal-header h3 {
  margin: 0;
  font-size: 1.5rem;
  color: var(--primary-dark);
}

.modal-subtitle {
  color: #64748b;
  margin: 0.1rem 0 0;
}

.modal-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  background: #fff7ed;
  color: #c2410c;
  border-radius: 999px;
  padding: 0.4rem 0.75rem;
  font-weight: 600;
  border: 1px solid #fed7aa;
}

.modal-badge.verified {
  background: #ecfdf3;
  color: #15803d;
  border-color: #bbf7d0;
}

.modal-body {
  margin: 0.5rem 0 1rem;
}

.modal-info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 0.75rem 1rem;
}

.modal-info-grid .label {
  font-size: 0.85rem;
  color: #94a3b8;
  margin: 0 0 0.15rem;
}

.modal-info-grid .value {
  margin: 0;
  font-weight: 700;
  color: var(--primary-dark);
}

.modal-info-grid .value.code {
  font-family: 'Fira Code', Consolas, monospace;
  font-size: 0.95rem;
}

.modal-info-grid .value.status {
  text-transform: capitalize;
}

.modal-alert {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  border-radius: 10px;
  margin-top: 0.75rem;
  font-weight: 600;
}

.modal-alert.error {
  background: #fef2f2;
  color: #991b1b;
  border: 1px solid #fecdd3;
}

.modal-alert.success {
  background: #ecfdf3;
  color: #166534;
  border: 1px solid #bbf7d0;
}

.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.25s ease;
}
.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}

.modal-rise-enter-active,
.modal-rise-leave-active {
  transition: all 0.25s ease;
}
.modal-rise-enter-from,
.modal-rise-leave-to {
  opacity: 0;
  transform: translateY(12px) scale(0.98);
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
}

.btn-outline,
.btn-primary,
.btn-secondary {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.65rem 1.1rem;
  border-radius: 10px;
  font-weight: 700;
  cursor: pointer;
  border: none;
}

.btn-outline {
  background: #ffffff;
  border: 1px solid var(--border);
  color: var(--primary-dark);
}

.btn-primary {
  background: linear-gradient(135deg, var(--accent) 0%, var(--primary) 100%);
  color: white;
}

.btn-secondary {
  background: #0f172a;
  color: #e2e8f0;
}

.btn-primary.gradient-text {
  background: linear-gradient(135deg, var(--accent), var(--primary));
  background-clip: text;
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.btn-outline:disabled,
.btn-primary:disabled,
.btn-secondary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.spinner {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
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
  .modal-actions .btn-outline span,
  .modal-actions .btn-primary span,
  .modal-actions .btn-secondary span {
    display: none;
  }
}
</style>
