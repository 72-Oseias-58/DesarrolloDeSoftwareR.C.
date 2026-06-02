import { defineStore } from 'pinia'
import api from '@/api/axios'
import router from '@/router'

const obtenerNombreRol = (usuario) => {
  return (
    usuario?.role?.nombre_rol ||
    usuario?.rol?.nombre_rol ||
    usuario?.role?.nombre ||
    usuario?.rol?.nombre ||
    usuario?.nombre_rol ||
    usuario?.rol ||
    null
  )
}

const redirigirPorRol = (rol) => {
  if (rol === 'SUPERADMIN') {
    router.push('/superadmin/dashboard')
    return
  }

  if (rol === 'ADMIN') {
    router.push('/admin/dashboard')
    return
  }

  if (rol === 'CAJERO') {
    router.push('/cajero/dashboard')
    return
  }

  router.push('/login')
}

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: localStorage.getItem('token') || null,
    usuario: JSON.parse(localStorage.getItem('usuario')) || null,
  }),

  getters: {
    estaAutenticado: (state) => !!state.token,

    rol: (state) => obtenerNombreRol(state.usuario),

    user: (state) => state.usuario,
  },

  actions: {
    async login(datos) {
      const response = await api.post('/login', datos)

      this.token = response.data.access_token
      this.usuario = response.data.user

      const rol = obtenerNombreRol(this.usuario)

      if (!this.token || !this.usuario || !rol) {
        console.error('Respuesta inesperada del backend:', response.data)
        throw new Error('La respuesta del servidor no contiene token, usuario o rol.')
      }

      localStorage.setItem('token', this.token)
      localStorage.setItem('usuario', JSON.stringify(this.usuario))

      redirigirPorRol(rol)
    },

    async obtenerUsuario() {
      try {
        const response = await api.get('/me')

        this.usuario = response.data.user

        localStorage.setItem('usuario', JSON.stringify(this.usuario))
      } catch (error) {
        console.error('Error al obtener usuario:', error)
        this.logout()
      }
    },

    async logout() {
      try {
        if (this.token) {
          await api.post('/logout')
        }
      } catch (error) {
        console.error('Error al cerrar sesión:', error)
      } finally {
        this.token = null
        this.usuario = null

        localStorage.removeItem('token')
        localStorage.removeItem('usuario')

        router.push('/login')
      }
    },
  },
})