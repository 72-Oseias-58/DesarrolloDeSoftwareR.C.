import {
  defineComponent,
  onMounted,
  reactive,
  ref,
} from 'vue'

import { useQuasar } from 'quasar'
import api from '@/api/axios'

export default defineComponent({
  name: 'SucursalesView',

  setup() {
    const $q = useQuasar()

    const sucursales = ref([])
    const cargando = ref(false)
    const cargandoListado = ref(false)
    const cargandoEditar = ref(false)
    const modalEditar = ref(false)

    const form = reactive({
      nombre: '',
      direccion: '',
      telefono: '',
    })

    const formEditar = reactive({
      id_sucursal: null,
      nombre: '',
      direccion: '',
      telefono: '',
    })

    const columnas = [
      {
        name: 'id_sucursal',
        label: 'ID',
        field: 'id_sucursal',
        align: 'left',
        sortable: true,
      },
      {
        name: 'nombre',
        label: 'Nombre',
        field: 'nombre',
        align: 'left',
        sortable: true,
      },
      {
        name: 'direccion',
        label: 'Dirección',
        field: 'direccion',
        align: 'left',
      },
      {
        name: 'telefono',
        label: 'Teléfono',
        field: 'telefono',
        align: 'left',
      },
      {
        name: 'estado',
        label: 'Estado',
        field: 'estado',
        align: 'center',
      },
      {
        name: 'created_at',
        label: 'Creado',
        field: 'created_at',
        align: 'left',
      },
      {
        name: 'acciones',
        label: 'Acciones',
        field: 'acciones',
        align: 'center',
      },
    ]

    const obtenerMensajeError = (
      error,
      mensajePredeterminado,
    ) => {
      return (
        error.response?.data?.message ||
        mensajePredeterminado
      )
    }

    const obtenerSucursales = async () => {
      cargandoListado.value = true

      try {
        const response = await api.get('/sucursales')

        sucursales.value = Array.isArray(
          response.data?.sucursales,
        )
          ? response.data.sucursales
          : []
      } catch (error) {
        console.error(
          'Error al obtener sucursales:',
          error,
        )

        $q.notify({
          type: 'negative',
          message: obtenerMensajeError(
            error,
            'No se pudieron cargar las sucursales.',
          ),
          position: 'top',
        })
      } finally {
        cargandoListado.value = false
      }
    }

    const limpiarFormulario = () => {
      form.nombre = ''
      form.direccion = ''
      form.telefono = ''
    }

    const crearSucursal = async () => {
      if (!form.nombre.trim()) {
        $q.notify({
          type: 'warning',
          message:
            'El nombre de la sucursal es obligatorio.',
          position: 'top',
        })

        return
      }

      cargando.value = true

      try {
        const response = await api.post(
          '/sucursales',
          {
            nombre: form.nombre.trim(),
            direccion:
              form.direccion.trim() || null,
            telefono:
              form.telefono.trim() || null,
          },
        )

        const nuevaSucursal =
          response.data?.sucursal

        if (nuevaSucursal) {
          sucursales.value.unshift(
            nuevaSucursal,
          )
        } else {
          await obtenerSucursales()
        }

        limpiarFormulario()

        $q.notify({
          type: 'positive',
          message:
            'Sucursal creada correctamente.',
          position: 'top',
          timeout: 1500,
        })
      } catch (error) {
        console.error(
          'Error al crear sucursal:',
          error,
        )

        $q.notify({
          type: 'negative',
          message: obtenerMensajeError(
            error,
            'No se pudo crear la sucursal.',
          ),
          position: 'top',
        })
      } finally {
        cargando.value = false
      }
    }

    const abrirEditar = (sucursal) => {
      formEditar.id_sucursal =
        sucursal.id_sucursal

      formEditar.nombre =
        sucursal.nombre || ''

      formEditar.direccion =
        sucursal.direccion || ''

      formEditar.telefono =
        sucursal.telefono || ''

      modalEditar.value = true
    }

    const actualizarSucursal = async () => {
      if (!formEditar.nombre.trim()) {
        $q.notify({
          type: 'warning',
          message:
            'El nombre de la sucursal es obligatorio.',
          position: 'top',
        })

        return
      }

      if (!formEditar.id_sucursal) {
        $q.notify({
          type: 'negative',
          message:
            'No se encontró la sucursal a editar.',
          position: 'top',
        })

        return
      }

      cargandoEditar.value = true

      try {
        const response = await api.put(
          `/sucursales/${formEditar.id_sucursal}`,
          {
            nombre:
              formEditar.nombre.trim(),
            direccion:
              formEditar.direccion.trim() ||
              null,
            telefono:
              formEditar.telefono.trim() ||
              null,
          },
        )

        const sucursalActualizada =
          response.data?.sucursal

        const indice =
          sucursales.value.findIndex(
            (sucursal) =>
              sucursal.id_sucursal ===
              formEditar.id_sucursal,
          )

        if (
          indice !== -1 &&
          sucursalActualizada
        ) {
          sucursales.value[indice] =
            sucursalActualizada
        } else {
          await obtenerSucursales()
        }

        modalEditar.value = false

        $q.notify({
          type: 'positive',
          message:
            'Sucursal actualizada correctamente.',
          position: 'top',
          timeout: 1500,
        })
      } catch (error) {
        console.error(
          'Error al actualizar sucursal:',
          error,
        )

        $q.notify({
          type: 'negative',
          message: obtenerMensajeError(
            error,
            'No se pudo actualizar la sucursal.',
          ),
          position: 'top',
        })
      } finally {
        cargandoEditar.value = false
      }
    }

    const cambiarEstadoSucursal = async (
      sucursal,
    ) => {
      const estadoAnterior = sucursal.estado

      const nuevoEstado =
        estadoAnterior === 'ACTIVA'
          ? 'INACTIVA'
          : 'ACTIVA'

      sucursal.estado = nuevoEstado

      try {
        const response = await api.patch(
          `/sucursales/${sucursal.id_sucursal}/estado`,
          {
            estado: nuevoEstado,
          },
        )

        const sucursalActualizada =
          response.data?.sucursal

        if (sucursalActualizada) {
          const indice =
            sucursales.value.findIndex(
              (item) =>
                item.id_sucursal ===
                sucursal.id_sucursal,
            )

          if (indice !== -1) {
            sucursales.value[indice] =
              sucursalActualizada
          }
        }

        $q.notify({
          type:
            nuevoEstado === 'ACTIVA'
              ? 'positive'
              : 'warning',
          message:
            `Sucursal cambiada a ${nuevoEstado}.`,
          position: 'top',
          timeout: 1500,
        })
      } catch (error) {
        sucursal.estado = estadoAnterior

        console.error(
          'Error al cambiar estado:',
          error,
        )

        $q.notify({
          type: 'negative',
          message: obtenerMensajeError(
            error,
            'No se pudo cambiar el estado de la sucursal.',
          ),
          position: 'top',
        })
      }
    }

    const formatearFecha = (fecha) => {
      if (!fecha) {
        return '-'
      }

      const fechaConvertida = new Date(fecha)

      if (
        Number.isNaN(
          fechaConvertida.getTime(),
        )
      ) {
        return '-'
      }

      return fechaConvertida.toLocaleDateString(
        'es-BO',
        {
          year: 'numeric',
          month: '2-digit',
          day: '2-digit',
        },
      )
    }

    onMounted(async () => {
      await obtenerSucursales()
    })

    return {
      sucursales,
      cargando,
      cargandoListado,
      cargandoEditar,
      modalEditar,
      form,
      formEditar,
      columnas,
      obtenerSucursales,
      crearSucursal,
      abrirEditar,
      actualizarSucursal,
      cambiarEstadoSucursal,
      formatearFecha,
    }
  },
})