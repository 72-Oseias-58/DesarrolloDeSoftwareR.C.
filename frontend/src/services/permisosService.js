import api from '@/api/axios'

export const permisosService = {
  async obtenerPermisosUsuario(idUsuario) {
    const response = await api.get(`/usuarios/${idUsuario}/permisos`)
    return response.data
  },

  async actualizarPermisosUsuario(idUsuario, payload) {
    const response = await api.put(`/usuarios/${idUsuario}/permisos`, payload)
    return response.data
  },

  async cambiarRolUsuario(idUsuario, rol) {
    const response = await api.patch(`/usuarios/${idUsuario}/rol`, { rol })
    return response.data
  },
}