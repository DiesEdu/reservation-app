<template>
  <div class="luxury-list-card">
    <div class="list-header">
      <div class="header-title">
        <div class="title-icon">
          <i class="bi bi-collection"></i>
        </div>
        <div>
          <h5 class="mb-0">Reservations</h5>
          <small class="text-gold-dim">Manage your dining experiences</small>
        </div>
      </div>
      <div class="filter-tabs">
        <button
          v-for="tab in tabs"
          :key="tab.value"
          @click="filter = tab.value"
          :class="['tab-btn', { active: filter === tab.value }]"
        >
          <i :class="tab.icon"></i>
          <span>{{ tab.label }}</span>
          <div class="tab-glow"></div>
        </button>
      </div>
    </div>

    <div class="list-body">
      <TransitionGroup name="reservation" tag="div" class="reservations-container">
        <div
          v-for="res in filteredReservations"
          :key="res.id"
          class="reservation-item"
          :class="`status-${res.status}`"
        >
          <div class="item-glow"></div>
          <div class="item-content">
            <div class="guest-info">
              <div class="avatar-ring">
                <div class="avatar">
                  {{ res.name.charAt(0) }}
                </div>
                <div class="ring-animation"></div>
              </div>
              <div class="guest-details">
                <h6 class="guest-name">{{ res.name }}</h6>
                <div class="guest-meta">
                  <span class="meta-item"><i class="bi bi-envelope"></i>{{ res.email }}</span>
                  <span class="meta-item"><i class="bi bi-telephone"></i>{{ res.phone }}</span>
                </div>
              </div>
            </div>

            <div class="reservation-info">
              <div class="info-pill date-pill">
                <i class="bi bi-calendar3"></i>
                <span>{{ formatDate(res.date) }}</span>
              </div>
              <div class="info-pill time-pill">
                <i class="bi bi-clock"></i>
                <span>{{ res.time }}</span>
              </div>
              <div class="info-pill guests-pill">
                <i class="bi bi-people"></i>
                <span>{{ res.guests }} Guests</span>
              </div>
            </div>

            <div class="table-info">
              <div class="table-badge">
                <i class="bi bi-shop"></i>
                <span>{{ res.table }}</span>
              </div>
              <div v-if="res.specialRequests" class="special-note">
                <i class="bi bi-stars"></i>
                <span>{{ res.specialRequests }}</span>
              </div>
            </div>

            <div class="status-actions">
              <div class="status-badge" :class="res.status">
                <span class="status-dot"></span>
                <span class="status-text">{{ res.status }}</span>
              </div>
              <div class="action-buttons">
                <button
                  v-if="res.status === 'pending'"
                  @click="confirmReservation(res.id)"
                  class="action-btn confirm"
                  title="Confirm"
                >
                  <i class="bi bi-check-lg"></i>
                </button>
                <button
                  v-if="res.status !== 'cancelled'"
                  @click="cancelReservation(res.id)"
                  class="action-btn cancel"
                  title="Cancel"
                >
                  <i class="bi bi-x-lg"></i>
                </button>
                <button @click="deleteReservation(res.id)" class="action-btn delete" title="Delete">
                  <i class="bi bi-trash3"></i>
                </button>
              </div>
            </div>
          </div>
        </div>
      </TransitionGroup>

      <div v-if="filteredReservations.length === 0" class="empty-state">
        <div class="empty-icon">
          <i class="bi bi-inbox"></i>
        </div>
        <h6>No reservations found</h6>
        <p>Create a new reservation to get started</p>
      </div>
    </div>

    <div class="list-footer">
      <div class="stats-line">
        <span class="stat-item">
          <i class="bi bi-circle-fill text-success"></i>
          {{ confirmedCount }} Confirmed
        </span>
        <span class="stat-item">
          <i class="bi bi-circle-fill text-warning"></i>
          {{ pendingCount }} Pending
        </span>
        <span class="stat-item">
          <i class="bi bi-circle-fill text-danger"></i>
          {{ cancelledCount }} Cancelled
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useReservationStore } from '../stores/reservations'

const store = useReservationStore()
const filter = ref('all')

const tabs = [
  { value: 'all', label: 'All', icon: 'bi bi-grid' },
  { value: 'confirmed', label: 'Confirmed', icon: 'bi bi-check-circle' },
  { value: 'pending', label: 'Pending', icon: 'bi bi-hourglass' },
  { value: 'cancelled', label: 'Cancelled', icon: 'bi bi-x-circle' },
]

const filteredReservations = computed(() => store.getByStatus(filter.value))

const confirmedCount = computed(
  () => store.reservations.filter((r) => r.status === 'confirmed').length,
)
const pendingCount = computed(() => store.reservations.filter((r) => r.status === 'pending').length)
const cancelledCount = computed(
  () => store.reservations.filter((r) => r.status === 'cancelled').length,
)

const confirmReservation = (id) => {
  store.updateStatus(id, 'confirmed')
}

const cancelReservation = (id) => {
  store.updateStatus(id, 'cancelled')
}

const deleteReservation = (id) => {
  if (confirm('Are you sure you want to delete this reservation?')) {
    store.deleteReservation(id)
  }
}

const formatDate = (dateStr) => {
  return new Date(dateStr).toLocaleDateString('en-US', {
    weekday: 'short',
    month: 'short',
    day: 'numeric',
  })
}
</script>

<style scoped>
.luxury-list-card {
  background: rgba(255, 255, 255, 0.03);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border: 1px solid rgba(212, 175, 55, 0.2);
  border-radius: 24px;
  overflow: hidden;
  box-shadow: 0 25px 50px rgba(0, 0, 0, 0.4);
  animation: slideIn 0.8s ease-out 0.2s both;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateX(30px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

.list-header {
  padding: 2rem;
  background: linear-gradient(135deg, rgba(212, 175, 55, 0.1) 0%, rgba(212, 175, 55, 0.05) 100%);
  border-bottom: 1px solid rgba(212, 175, 55, 0.2);
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1.5rem;
}

.header-title {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.title-icon {
  width: 50px;
  height: 50px;
  background: linear-gradient(135deg, rgba(212, 175, 55, 0.2) 0%, rgba(212, 175, 55, 0.1) 100%);
  border: 1px solid rgba(212, 175, 55, 0.3);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #d4af37;
  font-size: 1.5rem;
}

.header-title h5 {
  color: #f4e5c2;
  font-size: 1.3rem;
  font-weight: 600;
}

.text-gold-dim {
  color: rgba(244, 229, 194, 0.5);
  font-size: 0.85rem;
}

.filter-tabs {
  display: flex;
  gap: 0.5rem;
  background: rgba(0, 0, 0, 0.2);
  padding: 0.4rem;
  border-radius: 12px;
}

.tab-btn {
  position: relative;
  padding: 0.6rem 1.2rem;
  background: transparent;
  border: none;
  color: rgba(244, 229, 194, 0.6);
  font-size: 0.85rem;
  font-weight: 500;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  overflow: hidden;
}

.tab-btn:hover {
  color: #f4e5c2;
  background: rgba(212, 175, 55, 0.1);
}

.tab-btn.active {
  color: #0a0a0a;
  font-weight: 600;
}

.tab-glow {
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, #d4af37 0%, #f4e5c2 100%);
  border-radius: 8px;
  opacity: 0;
  transform: scale(0.8);
  transition: all 0.3s ease;
  z-index: -1;
}

.tab-btn.active .tab-glow {
  opacity: 1;
  transform: scale(1);
}

.list-body {
  padding: 1.5rem;
  max-height: 600px;
  overflow-y: auto;
}

.list-body::-webkit-scrollbar {
  width: 6px;
}

.list-body::-webkit-scrollbar-track {
  background: rgba(0, 0, 0, 0.1);
  border-radius: 3px;
}

.list-body::-webkit-scrollbar-thumb {
  background: rgba(212, 175, 55, 0.3);
  border-radius: 3px;
}

.reservations-container {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.reservation-item {
  position: relative;
  background: rgba(0, 0, 0, 0.2);
  border: 1px solid rgba(212, 175, 55, 0.1);
  border-radius: 16px;
  padding: 1.5rem;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  overflow: hidden;
}

.reservation-item:hover {
  transform: translateY(-2px) scale(1.01);
  border-color: rgba(212, 175, 55, 0.3);
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
}

.item-glow {
  position: absolute;
  top: 0;
  left: 0;
  width: 4px;
  height: 100%;
  opacity: 0;
  transition: opacity 0.3s ease;
}

.status-confirmed .item-glow {
  background: linear-gradient(to bottom, #28a745, #20c997);
  opacity: 1;
}
.status-pending .item-glow {
  background: linear-gradient(to bottom, #ffc107, #ff9800);
  opacity: 1;
}
.status-cancelled .item-glow {
  background: linear-gradient(to bottom, #dc3545, #c82333);
  opacity: 1;
}

.item-content {
  display: grid;
  grid-template-columns: 1.5fr 1fr 1fr auto;
  gap: 1.5rem;
  align-items: center;
}

.guest-info {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.avatar-ring {
  position: relative;
  width: 50px;
  height: 50px;
}

.avatar {
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, #d4af37 0%, #f4e5c2 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #0a0a0a;
  font-weight: 700;
  font-size: 1.2rem;
  position: relative;
  z-index: 2;
}

.ring-animation {
  position: absolute;
  inset: -4px;
  border: 2px solid transparent;
  border-top-color: #d4af37;
  border-radius: 50%;
  animation: rotate 3s linear infinite;
}

@keyframes rotate {
  to {
    transform: rotate(360deg);
  }
}

.guest-name {
  color: #f4e5c2;
  font-size: 1.1rem;
  font-weight: 600;
  margin-bottom: 0.3rem;
}

.guest-meta {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.meta-item {
  color: rgba(244, 229, 194, 0.5);
  font-size: 0.8rem;
  display: flex;
  align-items: center;
  gap: 0.4rem;
}

.meta-item i {
  color: #d4af37;
  font-size: 0.75rem;
}

.reservation-info {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.info-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.4rem 0.8rem;
  background: rgba(212, 175, 55, 0.1);
  border: 1px solid rgba(212, 175, 55, 0.2);
  border-radius: 20px;
  font-size: 0.85rem;
  color: #f4e5c2;
  width: fit-content;
}

.info-pill i {
  color: #d4af37;
  font-size: 0.8rem;
}

.table-info {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.table-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  background: linear-gradient(135deg, rgba(212, 175, 55, 0.2) 0%, rgba(212, 175, 55, 0.1) 100%);
  border: 1px solid rgba(212, 175, 55, 0.3);
  border-radius: 8px;
  color: #d4af37;
  font-weight: 600;
  font-size: 0.9rem;
  width: fit-content;
}

.special-note {
  display: flex;
  align-items: flex-start;
  gap: 0.5rem;
  padding: 0.5rem;
  background: rgba(255, 193, 7, 0.1);
  border-radius: 6px;
  font-size: 0.8rem;
  color: rgba(244, 229, 194, 0.7);
  max-width: 200px;
}

.special-note i {
  color: #ffc107;
  margin-top: 0.1rem;
}

.status-actions {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.8rem;
}

.status-badge {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.4rem 1rem;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.status-badge.confirmed {
  background: rgba(40, 167, 69, 0.2);
  color: #28a745;
  border: 1px solid rgba(40, 167, 69, 0.3);
}

.status-badge.pending {
  background: rgba(255, 193, 7, 0.2);
  color: #ffc107;
  border: 1px solid rgba(255, 193, 7, 0.3);
}

.status-badge.cancelled {
  background: rgba(220, 53, 69, 0.2);
  color: #dc3545;
  border: 1px solid rgba(220, 53, 69, 0.3);
}

.status-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  animation: pulse 2s infinite;
}

.status-badge.confirmed .status-dot {
  background: #28a745;
}
.status-badge.pending .status-dot {
  background: #ffc107;
}
.status-badge.cancelled .status-dot {
  background: #dc3545;
}

@keyframes pulse {
  0%,
  100% {
    opacity: 1;
    transform: scale(1);
  }
  50% {
    opacity: 0.5;
    transform: scale(1.2);
  }
}

.action-buttons {
  display: flex;
  gap: 0.5rem;
}

.action-btn {
  width: 36px;
  height: 36px;
  border: none;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 0.9rem;
}

.action-btn.confirm {
  background: rgba(40, 167, 69, 0.2);
  color: #28a745;
}

.action-btn.confirm:hover {
  background: #28a745;
  color: white;
  transform: scale(1.1);
  box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4);
}

.action-btn.cancel {
  background: rgba(255, 193, 7, 0.2);
  color: #ffc107;
}

.action-btn.cancel:hover {
  background: #ffc107;
  color: #0a0a0a;
  transform: scale(1.1);
  box-shadow: 0 4px 15px rgba(255, 193, 7, 0.4);
}

.action-btn.delete {
  background: rgba(220, 53, 69, 0.2);
  color: #dc3545;
}

.action-btn.delete:hover {
  background: #dc3545;
  color: white;
  transform: scale(1.1);
  box-shadow: 0 4px 15px rgba(220, 53, 69, 0.4);
}

.empty-state {
  text-align: center;
  padding: 4rem 2rem;
  color: rgba(244, 229, 194, 0.4);
}

.empty-icon {
  font-size: 4rem;
  margin-bottom: 1rem;
  opacity: 0.5;
}

.empty-state h6 {
  color: rgba(244, 229, 194, 0.7);
  margin-bottom: 0.5rem;
}

.list-footer {
  padding: 1.5rem 2rem;
  background: rgba(0, 0, 0, 0.2);
  border-top: 1px solid rgba(212, 175, 55, 0.1);
}

.stats-line {
  display: flex;
  gap: 2rem;
  justify-content: center;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: rgba(244, 229, 194, 0.6);
  font-size: 0.9rem;
}

.stat-item i {
  font-size: 0.6rem;
}

/* Transitions */
.reservation-enter-active,
.reservation-leave-active {
  transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

.reservation-enter-from {
  opacity: 0;
  transform: translateX(-30px) scale(0.9);
}

.reservation-leave-to {
  opacity: 0;
  transform: translateX(30px) scale(0.9);
}

.reservation-move {
  transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

@media (max-width: 1200px) {
  .item-content {
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
  }
  .status-actions {
    grid-column: 1 / -1;
    flex-direction: row;
    justify-content: space-between;
    align-items: center;
  }
}

@media (max-width: 768px) {
  .item-content {
    grid-template-columns: 1fr;
  }
  .filter-tabs {
    flex-wrap: wrap;
  }
}
</style>
