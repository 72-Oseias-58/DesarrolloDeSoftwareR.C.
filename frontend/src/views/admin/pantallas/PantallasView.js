import { computed, defineComponent, onMounted, ref } from 'vue'
import { useQuasar } from 'quasar'
import api from '@/api/axios'

export default defineComponent({
  name: 'PantallasView',

  setup() {
    const $q = useQuasar()

    const pantallas = ref([])
    const areas = ref([])

    const cargando = ref(false)
    const guardando = ref(false)
    const eliminando = ref(false)

    const mostrarDialogo = ref(false)
    const modoEdicion = ref(false)
    const pantallaEditando = ref(null)

    const formulario = ref({
      nombre: '',
      areas: [],
      permite_finalizar: false,
    })

    const opcionesAreas = computed(() => {
      return areas.value.map((area) => ({
        label: area.nombre_area,
        value: area.id_area,
      }))
    })

    const cantidadPantallas = computed(() => {
      return pantallas.value.length
    })

    const pantallaFinalizacion = computed(() => {
      return (
        pantallas.value.find(
          (pantalla) => Boolean(pantalla.permite_finalizar),
        ) || null
      )
    })

    const obtenerMensajeError = (error) => {
      const errores = error.response?.data?.errors

      if (errores) {
        const primerError = Object.values(errores)[0]

        if (Array.isArray(primerError)) {
          return primerError[0]
        }
      }

      return (
        error.response?.data?.message ||
        'Ocurrió un error inesperado.'
      )
    }

    const cargarPantallas = async () => {
      cargando.value = true

      try {
        const response = await api.get('/admin/pantallas')

        pantallas.value = Array.isArray(
          response.data?.pantallas,
        )
          ? response.data.pantallas
          : []

        areas.value = Array.isArray(response.data?.areas)
          ? response.data.areas
          : []
      } catch (error) {
        pantallas.value = []
        areas.value = []

        $q.notify({
          type: 'negative',
          message: obtenerMensajeError(error),
          position: 'top',
          timeout: 3500,
        })
      } finally {
        cargando.value = false
      }
    }

    const limpiarFormulario = () => {
      formulario.value = {
        nombre: '',
        areas: [],
        permite_finalizar: false,
      }

      modoEdicion.value = false
      pantallaEditando.value = null
    }

    const abrirNuevaPantalla = () => {
      limpiarFormulario()
      mostrarDialogo.value = true
    }

    const abrirEditarPantalla = (pantalla) => {
      modoEdicion.value = true
      pantallaEditando.value = pantalla

      formulario.value = {
        nombre: pantalla.nombre || '',
        areas: Array.isArray(pantalla.areas)
          ? pantalla.areas.map((area) =>
              Number(area.id_area),
            )
          : [],
        permite_finalizar: Boolean(
          pantalla.permite_finalizar,
        ),
      }

      mostrarDialogo.value = true
    }

    const cerrarDialogo = () => {
      mostrarDialogo.value = false
      limpiarFormulario()
    }

    const validarFormulario = () => {
      const nombre = String(
        formulario.value.nombre || '',
      ).trim()

      if (!nombre) {
        return 'Debe escribir el nombre de la pantalla.'
      }

      if (formulario.value.areas.length < 1) {
        return 'Debe seleccionar al menos un área.'
      }

      return null
    }

    const prepararPayload = () => {
      return {
        nombre: String(
          formulario.value.nombre || '',
        ).trim(),

        areas: formulario.value.areas.map((idArea) =>
          Number(idArea),
        ),

        permite_finalizar: Boolean(
          formulario.value.permite_finalizar,
        ),
      }
    }

    const guardarPantalla = async () => {
      const errorValidacion = validarFormulario()

      if (errorValidacion) {
        $q.notify({
          type: 'negative',
          message: errorValidacion,
          position: 'top',
          timeout: 3000,
        })

        return
      }

      guardando.value = true

      try {
        const payload = prepararPayload()

        if (
          modoEdicion.value &&
          pantallaEditando.value?.id_pantalla
        ) {
          await api.put(
            `/admin/pantallas/${pantallaEditando.value.id_pantalla}`,
            payload,
          )
        } else {
          await api.post('/admin/pantallas', payload)
        }

        $q.notify({
          type: 'positive',
          message: modoEdicion.value
            ? 'Pantalla actualizada correctamente.'
            : 'Pantalla registrada correctamente.',
          position: 'top',
          timeout: 2200,
        })

        cerrarDialogo()
        await cargarPantallas()
      } catch (error) {
        $q.notify({
          type: 'negative',
          message: obtenerMensajeError(error),
          position: 'top',
          timeout: 3500,
        })
      } finally {
        guardando.value = false
      }
    }

    const confirmarEliminar = (pantalla) => {
      $q.dialog({
        title: 'Eliminar pantalla',
        message:
          `¿Desea eliminar la pantalla "${pantalla.nombre}"? ` +
          'También se eliminarán sus áreas asignadas.',
        cancel: {
          flat: true,
          label: 'Cancelar',
        },
        ok: {
          color: 'negative',
          label: 'Eliminar',
          unelevated: true,
        },
        persistent: true,
      }).onOk(async () => {
        eliminando.value = true

        try {
          await api.delete(
            `/admin/pantallas/${pantalla.id_pantalla}`,
          )

          $q.notify({
            type: 'positive',
            message: 'Pantalla eliminada correctamente.',
            position: 'top',
            timeout: 2200,
          })

          await cargarPantallas()
        } catch (error) {
          $q.notify({
            type: 'negative',
            message: obtenerMensajeError(error),
            position: 'top',
            timeout: 3500,
          })
        } finally {
          eliminando.value = false
        }
      })
    }

    const nombresAreas = (pantalla) => {
      if (
        !Array.isArray(pantalla?.areas) ||
        pantalla.areas.length === 0
      ) {
        return 'Sin áreas'
      }

      return pantalla.areas
        .map((area) => area.nombre_area)
        .join(', ')
    }

    const colorArea = (nombreArea) => {
      const nombre = String(nombreArea || '')
        .toUpperCase()
        .trim()

      if (nombre === 'GUARNICIONES') {
        return 'orange'
      }

      if (nombre === 'CARNE') {
        return 'red'
      }

      if (nombre === 'BEBIDAS') {
        return 'blue'
      }

      return 'grey'
    }

    onMounted(async () => {
      await cargarPantallas()
    })

    return {
      pantallas,
      areas,
      cargando,
      guardando,
      eliminando,

      mostrarDialogo,
      modoEdicion,
      pantallaEditando,
      formulario,

      opcionesAreas,
      cantidadPantallas,
      pantallaFinalizacion,

      cargarPantallas,
      abrirNuevaPantalla,
      abrirEditarPantalla,
      cerrarDialogo,
      guardarPantalla,
      confirmarEliminar,

      nombresAreas,
      colorArea,
    }
  },
})