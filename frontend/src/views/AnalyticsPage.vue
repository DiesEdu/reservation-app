<template>
  <div class="analytics-page">
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
                    <div v-for="segment in statusSegments" :key="segment.label" class="legend-item">
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
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useReservationStore } from '../stores/reservations'

const store = useReservationStore()
const timeFilter = ref('all')

// Fetch reservations on mount
onMounted(() => {
  store.fetchReservations()
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
</script>

<style scoped>
.analytics-page {
  min-height: 100vh;
  background: #0a0a0a;
  color: #f4e5c2;
  padding-bottom: 4rem;
}

/* Header */
.analytics-header {
  background: linear-gradient(180deg, rgba(212, 175, 55, 0.1) 0%, transparent 100%);
  padding: 2rem 0;
  border-bottom: 1px solid rgba(212, 175, 55, 0.1);
  animation: fadeIn 0.5s ease-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
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
  color: #f4e5c2;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.page-title i {
  color: #d4af37;
}

.page-subtitle {
  color: rgba(244, 229, 194, 0.6);
  margin-top: 0.25rem;
  font-size: 0.9rem;
}

.filter-select {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(212, 175, 55, 0.3);
  color: #f4e5c2;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  font-size: 0.9rem;
  cursor: pointer;
  transition: all 0.3s ease;
}

.filter-select:hover,
.filter-select:focus {
  border-color: #d4af37;
  outline: none;
}

.filter-select option {
  background: #1a1a1a;
  color: #f4e5c2;
}

/* Analytics Content */
.analytics-content {
  padding-top: 2rem;
}

/* Summary Stats */
.summary-section {
  margin-bottom: 2rem;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1.5rem;
}

.stat-card {
  background: rgba(255, 255, 255, 0.03);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(212, 175, 55, 0.2);
  border-radius: 16px;
  padding: 1.5rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  animation: slideUp 0.5s ease-out backwards;
  transition: all 0.3s ease;
}

.stat-card:hover {
  border-color: rgba(212, 175, 55, 0.4);
  transform: translateY(-3px);
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.stat-icon {
  width: 50px;
  height: 50px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  flex-shrink: 0;
}

.stat-icon.gold {
  background: linear-gradient(135deg, rgba(212, 175, 55, 0.2) 0%, rgba(212, 175, 55, 0.1) 100%);
  color: #d4af37;
}

.stat-icon.success {
  background: linear-gradient(135deg, rgba(40, 167, 69, 0.2) 0%, rgba(40, 167, 69, 0.1) 100%);
  color: #28a745;
}

.stat-icon.warning {
  background: linear-gradient(135deg, rgba(255, 193, 7, 0.2) 0%, rgba(255, 193, 7, 0.1) 100%);
  color: #ffc107;
}

.stat-icon.info {
  background: linear-gradient(135deg, rgba(23, 162, 184, 0.2) 0%, rgba(23, 162, 184, 0.1) 100%);
  color: #17a2b8;
}

.stat-details {
  flex: 1;
}

.stat-value {
  font-size: 1.75rem;
  font-weight: 700;
  color: #f4e5c2;
  line-height: 1;
}

.stat-label {
  font-size: 0.85rem;
  color: rgba(244, 229, 194, 0.6);
  margin-top: 0.25rem;
}

.stat-trend {
  font-size: 0.75rem;
  margin-top: 0.5rem;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.stat-trend.positive {
  color: #28a745;
}

.stat-trend.negative {
  color: #dc3545;
}

/* Charts Section */
.charts-section {
  margin-bottom: 2rem;
}

.chart-card {
  background: rgba(255, 255, 255, 0.03);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(212, 175, 55, 0.2);
  border-radius: 16px;
  overflow: hidden;
  height: 100%;
}

.chart-header {
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid rgba(212, 175, 55, 0.1);
}

.chart-title {
  font-size: 1rem;
  font-weight: 600;
  color: #f4e5c2;
  margin: 0;
}

.chart-body {
  padding: 1.5rem;
}

/* Donut Chart */
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
  font-weight: 700;
  color: #f4e5c2;
  line-height: 1;
}

.donut-label {
  font-size: 0.75rem;
  color: rgba(244, 229, 194, 0.6);
}

.chart-legend {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.legend-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  flex-shrink: 0;
}

.legend-label {
  flex: 1;
  font-size: 0.85rem;
  color: rgba(244, 229, 194, 0.8);
}

.legend-value {
  font-weight: 600;
  color: #f4e5c2;
}

.legend-percent {
  font-size: 0.75rem;
  color: rgba(244, 229, 194, 0.5);
  width: 35px;
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
  max-width: 40px;
  background: linear-gradient(180deg, #d4af37 0%, rgba(212, 175, 55, 0.5) 100%);
  border-radius: 6px 6px 0 0;
  min-height: 4px;
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
  filter: brightness(1.2);
}

.bar-value {
  position: absolute;
  top: -25px;
  left: 50%;
  transform: translateX(-50%);
  font-size: 0.75rem;
  font-weight: 600;
  color: #f4e5c2;
}

.bar-label {
  font-size: 0.7rem;
  color: rgba(244, 229, 194, 0.6);
  margin-top: 0.5rem;
}

/* Guests Distribution */
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
  height: 24px;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 12px;
  overflow: hidden;
}

.guest-bar-fill {
  height: 100%;
  background: linear-gradient(90deg, #d4af37 0%, #f4e5c2 100%);
  border-radius: 12px;
  transition: width 0.8s ease-out;
}

.guest-range {
  width: 80px;
  font-size: 0.8rem;
  color: rgba(244, 229, 194, 0.8);
}

.guest-count {
  width: 30px;
  text-align: right;
  font-weight: 600;
  color: #f4e5c2;
}

/* Timeline Chart */
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
  font-size: 0.75rem;
  color: rgba(244, 229, 194, 0.6);
}

.timeline-bar-wrapper {
  flex: 1;
  height: 20px;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 10px;
  overflow: hidden;
}

.timeline-bar {
  height: 100%;
  background: linear-gradient(90deg, rgba(212, 175, 55, 0.3) 0%, #d4af37 100%);
  border-radius: 10px;
  transition: width 0.8s ease-out;
}

.timeline-count {
  width: 25px;
  text-align: right;
  font-size: 0.8rem;
  font-weight: 600;
  color: #f4e5c2;
}

/* Table Grid */
.table-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
  gap: 1rem;
}

.table-card {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(212, 175, 55, 0.1);
  border-radius: 12px;
  padding: 1rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.3s ease;
}

.table-card.occupied {
  border-color: rgba(212, 175, 55, 0.3);
  background: rgba(212, 175, 55, 0.05);
}

.table-icon {
  font-size: 1.5rem;
  color: rgba(244, 229, 194, 0.4);
}

.table-card.occupied .table-icon {
  color: #d4af37;
}

.table-info {
  text-align: center;
}

.table-number {
  display: block;
  font-weight: 600;
  color: #f4e5c2;
  font-size: 0.9rem;
}

.table-capacity {
  font-size: 0.7rem;
  color: rgba(244, 229, 194, 0.5);
}

.table-status {
  text-align: center;
}

.status-count {
  display: block;
  font-size: 1.25rem;
  font-weight: 700;
  color: #f4e5c2;
  line-height: 1;
}

.status-label {
  font-size: 0.65rem;
  color: rgba(244, 229, 194, 0.5);
  text-transform: uppercase;
}

/* Activity Section */
.activity-section {
  margin-bottom: 2rem;
}

.activity-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.activity-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.75rem;
  background: rgba(255, 255, 255, 0.02);
  border-radius: 10px;
  transition: all 0.3s ease;
}

.activity-item:hover {
  background: rgba(255, 255, 255, 0.05);
}

.activity-avatar {
  width: 40px;
  height: 40px;
  background: rgba(212, 175, 55, 0.1);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #d4af37;
  font-size: 1rem;
}

.activity-details {
  flex: 1;
}

.activity-name {
  display: block;
  font-weight: 500;
  color: #f4e5c2;
  font-size: 0.9rem;
}

.activity-meta {
  font-size: 0.75rem;
  color: rgba(244, 229, 194, 0.5);
}

.activity-status {
  font-size: 0.7rem;
  font-weight: 600;
  text-transform: uppercase;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
}

.activity-status.confirmed {
  background: rgba(40, 167, 69, 0.2);
  color: #28a745;
}

.activity-status.pending {
  background: rgba(255, 193, 7, 0.2);
  color: #ffc107;
}

.activity-status.cancelled {
  background: rgba(220, 53, 69, 0.2);
  color: #dc3545;
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
  background: rgba(255, 255, 255, 0.02);
  border-radius: 10px;
  transition: all 0.3s ease;
}

.insight-item:hover {
  background: rgba(255, 255, 255, 0.05);
}

.insight-icon {
  width: 44px;
  height: 44px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
}

.insight-icon.success {
  background: rgba(40, 167, 69, 0.15);
  color: #28a745;
}

.insight-icon.warning {
  background: rgba(255, 193, 7, 0.15);
  color: #ffc107;
}

.insight-icon.info {
  background: rgba(23, 162, 184, 0.15);
  color: #17a2b8;
}

.insight-icon.gold {
  background: rgba(212, 175, 55, 0.15);
  color: #d4af37;
}

.insight-content {
  flex: 1;
}

.insight-label {
  display: block;
  font-size: 0.8rem;
  color: rgba(244, 229, 194, 0.6);
}

.insight-value {
  font-size: 1.25rem;
  font-weight: 700;
  color: #f4e5c2;
}

/* Responsive */
@media (max-width: 768px) {
  .page-title {
    font-size: 1.5rem;
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
}
</style>
