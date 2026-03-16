import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useReservationStore = defineStore('reservations', () => {
  const reservations = ref([
    {
      id: 1,
      name: 'Sarah Johnson',
      email: 'sarah.j@email.com',
      phone: '+1 (555) 123-4567',
      date: '2026-03-20',
      time: '19:00',
      guests: 4,
      table: 'Window Table 5',
      status: 'confirmed',
      specialRequests: 'Anniversary dinner, please prepare a small cake',
      createdAt: new Date('2026-03-15'),
    },
    {
      id: 2,
      name: 'Michael Chen',
      email: 'mchen@email.com',
      phone: '+1 (555) 987-6543',
      date: '2026-03-21',
      time: '20:30',
      guests: 2,
      table: 'Booth 3',
      status: 'pending',
      specialRequests: 'Vegetarian menu options',
      createdAt: new Date('2026-03-15'),
    },
    {
      id: 3,
      name: 'Emma Williams',
      email: 'emma.w@email.com',
      phone: '+1 (555) 456-7890',
      date: '2026-03-22',
      time: '18:00',
      guests: 6,
      table: 'Private Room A',
      status: 'confirmed',
      specialRequests: 'Birthday celebration, need high chair for toddler',
      createdAt: new Date('2026-03-14'),
    },
    {
      id: 4,
      name: 'James Rodriguez',
      email: 'j.rodriguez@email.com',
      phone: '+1 (555) 234-5678',
      date: '2026-03-19',
      time: '19:30',
      guests: 3,
      table: 'Table 12',
      status: 'cancelled',
      specialRequests: '',
      createdAt: new Date('2026-03-14'),
    },
    {
      id: 5,
      name: 'Lisa Thompson',
      email: 'lisa.t@email.com',
      phone: '+1 (555) 876-5432',
      date: '2026-03-23',
      time: '21:00',
      guests: 2,
      table: 'Bar Seating',
      status: 'confirmed',
      specialRequests: 'Wine pairing menu preferred',
      createdAt: new Date('2026-03-13'),
    },
  ])

  const nextId = computed(() => {
    return Math.max(...reservations.value.map((r) => r.id), 0) + 1
  })

  const addReservation = (reservation) => {
    reservations.value.unshift({
      ...reservation,
      id: nextId.value,
      status: 'pending',
      createdAt: new Date(),
    })
  }

  const updateStatus = (id, status) => {
    const res = reservations.value.find((r) => r.id === id)
    if (res) res.status = status
  }

  const deleteReservation = (id) => {
    const index = reservations.value.findIndex((r) => r.id === id)
    if (index > -1) reservations.value.splice(index, 1)
  }

  const getByStatus = (status) => {
    if (status === 'all') return reservations.value
    return reservations.value.filter((r) => r.status === status)
  }

  return {
    reservations,
    addReservation,
    updateStatus,
    deleteReservation,
    getByStatus,
  }
})
