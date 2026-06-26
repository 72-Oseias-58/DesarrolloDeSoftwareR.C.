import api from '@/api/axios'

const construirParametros = ({
  periodo = 'hoy',
  fechaDesde = null,
  fechaHasta = null,
} = {}) => {
  const params = {
    periodo,
  }

  if (periodo === 'personalizado') {
    params.fecha_desde = fechaDesde
    params.fecha_hasta = fechaHasta
  }

  return params
}

const obtenerDatosRespuesta = (response) => {
  return response?.data?.data ?? null
}

const obtenerEstadisticasGlobales = async (filtros = {}) => {
  const response = await api.get(
    '/superadmin/estadisticas/ventas',
    {
      params: construirParametros(filtros),
    },
  )

  return obtenerDatosRespuesta(response)
}

const obtenerEstadisticasSucursal = async (
  idSucursal,
  filtros = {},
) => {
  if (!idSucursal) {
    throw new Error('El identificador de la sucursal es obligatorio.')
  }

  const response = await api.get(
    `/superadmin/sucursales/${idSucursal}/estadisticas/ventas`,
    {
      params: construirParametros(filtros),
    },
  )

  return obtenerDatosRespuesta(response)
}

const obtenerEstadisticasAdmin = async (filtros = {}) => {
  const response = await api.get(
    '/admin/estadisticas/ventas',
    {
      params: construirParametros(filtros),
    },
  )

  return obtenerDatosRespuesta(response)
}

export default {
  obtenerEstadisticasGlobales,
  obtenerEstadisticasSucursal,
  obtenerEstadisticasAdmin,
}