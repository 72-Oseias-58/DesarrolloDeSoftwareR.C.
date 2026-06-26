import { defineStore } from 'pinia'
import api from '@/api/axios'
import router from '@/router'
import { initEcho, getEcho, disconnectEcho } from '@/echo'

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

const obtenerPermisos = (usuario) => {
  if (!usuario) return []

  if (Array.isArray(usuario.permisos)) {
    return usuario.permisos
  }

  return []
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

// const actualizarHeaderEcho = () => {
//   const token = localStorage.getItem('token')

//   if (echo?.connector?.pusher?.config?.auth?.headers) {
//     echo.connector.pusher.config.auth.headers.Authorization = `Bearer ${token}`
//   }
// }

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: localStorage.getItem('token') || null,
    usuario: JSON.parse(localStorage.getItem('usuario')) || null,

    canalPermisosActivo: false,
    canalPermisosUserId: null,
  }),

  getters: {
    estaAutenticado: (state) => !!state.token,

    rol: (state) => obtenerNombreRol(state.usuario),

    user: (state) => state.usuario,

    permisos: (state) => obtenerPermisos(state.usuario),

    tienePermiso: (state) => {
      return (permiso) => {
        const permisos = obtenerPermisos(state.usuario)
        return permisos.includes(permiso)
      }
    },

    tieneAlgunPermiso: (state) => {
      return (permisosNecesarios = []) => {
        const permisos = obtenerPermisos(state.usuario)
        return permisosNecesarios.some((permiso) => permisos.includes(permiso))
      }
    },
  },

  actions: {
    async login(datos) {
      try {
        const response = await api.post('/login', datos)

        this.token = response.data.access_token
        this.usuario = response.data.user

        const rol = obtenerNombreRol(this.usuario)

        if (!this.token || !this.usuario || !rol) {
          console.error('Respuesta inesperada del backend:', response.data)

          return {
            ok: false,
            message: 'La respuesta del servidor no contiene token, usuario o rol.',
          }
        }

        localStorage.setItem('token', this.token)
        localStorage.setItem('usuario', JSON.stringify(this.usuario))

        initEcho(this.token)
        this.escucharPermisosTiempoReal()

        redirigirPorRol(rol)

        return {
          ok: true,
          message: 'Inicio de sesión correcto.',
        }
      } catch (error) {
        const status = error.response?.status

        if (status === 401) {
          return {
            ok: false,
            message: 'Usuario o contraseña incorrectos.',
          }
        }

        if (status === 422) {
          return {
            ok: false,
            message: 'Debe ingresar usuario y contraseña.',
          }
        }

        if (status === 500) {
          console.error('Error interno en login:', error)

          return {
            ok: false,
            message: 'Error interno del servidor.',
          }
        }

        console.error('Error inesperado en login:', error)

        return {
          ok: false,
          message: 'No se pudo conectar con el servidor.',
        }
      }
    },

    async obtenerUsuario() {
      try {
        const response = await api.get('/me')

        this.usuario = response.data.user

        localStorage.setItem('usuario', JSON.stringify(this.usuario))

        initEcho(this.token)
        this.escucharPermisosTiempoReal()
      } catch (error) {
        console.error('Error al obtener usuario:', error)
        this.logout()
      }
    },

    async actualizarUsuarioDesdeMe() {
      try {
        const response = await api.get('/me')

        this.usuario = response.data.user

        localStorage.setItem('usuario', JSON.stringify(this.usuario))

        this.verificarPermisoRutaActual()
      } catch (error) {
        console.error('Error al actualizar usuario por cambio de permisos:', error)
        this.logout()
      }
    },

    escucharPermisosTiempoReal() {
      if (!this.token || !this.usuario?.id) return

      const userId = this.usuario.id

      if (this.canalPermisosActivo && this.canalPermisosUserId === userId) {
        return
      }

      this.detenerPermisosTiempoReal()

      const echo = initEcho(this.token)

      if (!echo) return

      echo.private(`user.${userId}`).listen('.permission.changed', async (evento) => {
        console.log('Permisos actualizados en tiempo real:', evento)

        if (evento?.permisos && Array.isArray(evento.permisos)) {
          this.usuario = {
            ...this.usuario,
            permisos: evento.permisos,
          }

          localStorage.setItem('usuario', JSON.stringify(this.usuario))
        }

        await this.actualizarUsuarioDesdeMe()
      })

      this.canalPermisosActivo = true
      this.canalPermisosUserId = userId
    },

    detenerPermisosTiempoReal() {
      const echo = getEcho()

      if (echo && this.canalPermisosUserId) {
        echo.leave(`user.${this.canalPermisosUserId}`)
      }

      this.canalPermisosActivo = false
      this.canalPermisosUserId = null
    },

    verificarPermisoRutaActual() {
      const permisoRuta = router.currentRoute.value.meta?.permiso

      if (!permisoRuta) return

      const permisosUsuario = obtenerPermisos(this.usuario)

      let tieneAcceso = true

      if (Array.isArray(permisoRuta)) {
        tieneAcceso = permisoRuta.some((permiso) => permisosUsuario.includes(permiso))
      } else {
        tieneAcceso = permisosUsuario.includes(permisoRuta)
      }

      if (!tieneAcceso) {
        redirigirPorRol(obtenerNombreRol(this.usuario))
      }
    },

    async logout() {
      try {
        this.detenerPermisosTiempoReal()

        if (this.token) {
          await api.post('/logout')
        }
      } catch (error) {
        console.error('Error al cerrar sesión:', error)
      } finally {
        this.detenerPermisosTiempoReal()
        disconnectEcho()

        this.token = null
        this.usuario = null

        localStorage.removeItem('token')
        localStorage.removeItem('usuario')

        router.push('/login')
      }
    },
  },
})
