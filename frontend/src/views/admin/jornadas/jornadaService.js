import api from '@/api/axios'

const jornadaService = {
  async obtenerActual() {
    const response = await api.get('/jornadas/actual')
    return response.data
  },

  async obtenerHistorial() {
    const response = await api.get('/jornadas')
    return response.data
  },

  async obtenerTiposCarne() {
    const response = await api.get(
      '/jornadas/tipos-carne',
    )

    return response.data
  },

  async abrir(payload) {
    const response = await api.post(
      '/jornadas/abrir',
      payload,
    )

    return response.data
  },

  async prepararCierre() {
    const response = await api.get(
      '/jornadas/preparar-cierre',
    )

    return response.data
  },

  async cerrar(payload) {
    const response = await api.patch(
      '/jornadas/cerrar',
      payload,
    )

    return response.data
  },
}

export default jornadaService