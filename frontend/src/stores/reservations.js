import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

// API Base URL - uses relative path for Vite proxy
const API_URL = 'http://localhost:8000/api'
// const API_URL = 'https://reserve.resonanz.id/api'

export const useReservationStore = defineStore('reservations', () => {
  const reservations = ref([])
  const loading = ref(false)
  const error = ref(null)

  // Fetch all reservations from the backend
  const fetchReservations = async (status = null) => {
    loading.value = true
    error.value = null
    try {
      let url = `${API_URL}/reservations`
      if (status && status !== 'all') {
        url += `?status=${status}`
      }
      const response = await fetch(url)
      const data = await response.json()

      if (data.success) {
        reservations.value = data.data
      } else {
        error.value = data.error || 'Failed to fetch reservations'
      }
    } catch (err) {
      error.value = 'Network error: Unable to connect to server'
      console.error('Error fetching reservations:', err)
    } finally {
      loading.value = false
    }
  }

  // Add a new reservation
  const addReservation = async (reservation) => {
    loading.value = true
    error.value = null
    try {
      const response = await fetch(`${API_URL}/reservations`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          name: reservation.name,
          email: reservation.email,
          phone: reservation.phone,
          date: reservation.date,
          time: reservation.time,
          guests: reservation.guests,
          table: reservation.table,
          specialRequests: reservation.specialRequests,
        }),
      })

      const data = await response.json()

      if (data.success) {
        // Add the new reservation to the beginning of the list
        reservations.value.unshift(data.data)
        return true
      } else {
        error.value = data.error || 'Failed to create reservation'
        return false
      }
    } catch (err) {
      error.value = 'Network error: Unable to connect to server'
      console.error('Error creating reservation:', err)
      return false
    } finally {
      loading.value = false
    }
  }

  // Update reservation status (confirm/cancel)
  const updateStatus = async (id, status) => {
    error.value = null
    try {
      const response = await fetch(`${API_URL}/reservations/status`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ id, status }),
      })

      const data = await response.json()

      if (data.success) {
        // Update the local state
        const reservation = reservations.value.find((r) => r.id === id)
        if (reservation) {
          reservation.status = status
        }
        return true
      } else {
        error.value = data.error || 'Failed to update status'
        return false
      }
    } catch (err) {
      error.value = 'Network error: Unable to connect to server'
      console.error('Error updating status:', err)
      return false
    }
  }

  // Delete a reservation
  const deleteReservation = async (id) => {
    error.value = null
    try {
      const response = await fetch(`${API_URL}/reservations/${id}`, {
        method: 'DELETE',
      })

      const data = await response.json()

      if (data.success) {
        // Remove from local state
        const index = reservations.value.findIndex((r) => r.id === id)
        if (index > -1) {
          reservations.value.splice(index, 1)
        }
        return true
      } else {
        error.value = data.error || 'Failed to delete reservation'
        return false
      }
    } catch (err) {
      error.value = 'Network error: Unable to connect to server'
      console.error('Error deleting reservation:', err)
      return false
    }
  }

  // Get reservations by status
  const getByStatus = (status) => {
    if (status === 'all') return reservations.value
    return reservations.value.filter((r) => r.status === status)
  }

  // Computed properties for counts
  const confirmedCount = computed(
    () => reservations.value.filter((r) => r.status === 'confirmed').length,
  )
  const pendingCount = computed(
    () => reservations.value.filter((r) => r.status === 'pending').length,
  )
  const cancelledCount = computed(
    () => reservations.value.filter((r) => r.status === 'cancelled').length,
  )

  return {
    reservations,
    loading,
    error,
    fetchReservations,
    addReservation,
    updateStatus,
    deleteReservation,
    getByStatus,
    confirmedCount,
    pendingCount,
    cancelledCount,
  }
})
