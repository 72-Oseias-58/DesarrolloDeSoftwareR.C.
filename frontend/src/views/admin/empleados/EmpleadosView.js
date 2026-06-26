import { computed, defineComponent, onMounted, ref } from 'vue'
import { useQuasar } from 'quasar'
import { useRouter } from 'vue-router'
import api from '@/api/axios'

export default defineComponent({
  name: 'EmpleadosView',

  setup() {
    const q = useQuasar()
    const router = useRouter()

    const empleados = ref([])
    const filtro = ref('')
    const loading = ref(false)
    const guardando = ref(false)

    const modal = ref(false)
    const modalDetalle = ref(false)
    const editando = ref(false)

    const empleadoEditId = ref(null)
    const empleadoDetalle = ref(null)
    const verPassword = ref(false)

    const cargosBase = [
      'CAJERO/A',
      'COCINERO/A',
      'MESERO/A',
      'LLAMADOR/A',
      'AYUDANTE DE COCINA',
    ]

    const cargosPersonalizados = ref([])

    const columns = [
      { name: 'nombre', label: 'Nombre', field: 'nombre', align: 'left' },
      { name: 'cargo', label: 'Cargo', field: 'cargo', align: 'left' },
      { name: 'usuario', label: 'Usuario', field: 'usuario', align: 'left' },
      { name: 'telefono', label: 'Teléfono', field: 'telefono', align: 'left' },
      { name: 'estado', label: 'Estado', field: 'estado', align: 'center' },
      { name: 'acciones', label: 'Acciones', field: 'acciones', align: 'center' },
    ]

    const form = ref({
      nombre: '',
      cargo: '',
      telefono: '',
      fecha_nacimiento: '',
      contacto_referencia: '',
      telefono_referencia: '',
      usuario: '',
      email: '',
      password: '',
    })

    const normalizarTexto = (texto) => {
      return String(texto || '')
        .trim()
        .toUpperCase()
    }

    const esCargoCajero = (cargo) => {
      return normalizarTexto(cargo).includes('CAJERO')
    }

    const cargos = computed(() => {
      const cargosUsados = empleados.value
        .map((empleado) => normalizarTexto(empleado.cargo))
        .filter(Boolean)

      return [...new Set([...cargosBase, ...cargosPersonalizados.value, ...cargosUsados])]
    })

    const agregarCargo = (nuevoCargo, done) => {
      const cargo = normalizarTexto(nuevoCargo)

      if (!cargo) {
        done()
        return
      }

      if (!cargos.value.includes(cargo)) {
        cargosPersonalizados.value.push(cargo)
      }

      done(cargo, 'add-unique')
    }

    const esCajeroConUsuario = (empleado) => {
      const cargo = empleado?.cargo || ''
      const rol =
        empleado?.usuario?.rol?.nombre ||
        empleado?.usuario?.role?.nombre_rol ||
        empleado?.usuario?.role?.nombre ||
        ''

      return empleado?.id_user && (esCargoCajero(cargo) || normalizarTexto(rol) === 'CAJERO')
    }

    const abrirPermisosCajero = (empleado) => {
      if (!empleado?.id_user) {
        q.notify({
          type: 'negative',
          message: 'Este empleado no tiene usuario del sistema.',
          position: 'top',
        })
        return
      }

      router.push(`/admin/usuarios/${empleado.id_user}/permisos`)
    }

    const inicialesEmpleado = computed(() => {
      if (!empleadoDetalle.value?.nombre) return 'EM'

      return empleadoDetalle.value.nombre
        .split(' ')
        .slice(0, 2)
        .map((p) => p[0])
        .join('')
        .toUpperCase()
    })

    const limpiarForm = () => {
      form.value = {
        nombre: '',
        cargo: '',
        telefono: '',
        fecha_nacimiento: '',
        contacto_referencia: '',
        telefono_referencia: '',
        usuario: '',
        email: '',
        password: '',
      }

      verPassword.value = false
    }

    const cargarEmpleados = async () => {
      loading.value = true

      try {
        const res = await api.get('/empleados')
        empleados.value = res.data.empleados || []
      } catch (error) {
        q.notify({
          type: 'negative',
          message: error.response?.data?.message || 'Error al cargar empleados',
        })
      } finally {
        loading.value = false
      }
    }

    const abrirModalCrear = () => {
      editando.value = false
      empleadoEditId.value = null
      limpiarForm()
      modal.value = true
    }

    const guardarEmpleado = async () => {
      guardando.value = true

      try {
        const payload = { ...form.value }

        if (!esCargoCajero(payload.cargo)) {
          payload.usuario = ''
          payload.email = ''
          payload.password = ''
        }

        if (esCargoCajero(payload.cargo)) {
          if (!payload.usuario) {
            q.notify({
              type: 'negative',
              message: 'El cajero debe tener usuario para acceder al sistema.',
              position: 'top',
            })
            return
          }

          if (!editando.value && !payload.password) {
            q.notify({
              type: 'negative',
              message: 'El cajero debe tener contraseña.',
              position: 'top',
            })
            return
          }
        }

        if (editando.value) {
          await api.put(`/empleados/${empleadoEditId.value}`, payload)

          q.notify({
            type: 'positive',
            message: 'Empleado actualizado',
          })
        } else {
          await api.post('/empleados', payload)

          q.notify({
            type: 'positive',
            message: 'Empleado creado',
          })
        }

        modal.value = false
        limpiarForm()
        cargarEmpleados()
      } catch (error) {
        console.log('ERROR EMPLEADO:', error.response?.data || error)

        q.notify({
          type: 'negative',
          message:
            error.response?.data?.error ||
            error.response?.data?.message ||
            'Error al guardar empleado',
        })
      } finally {
        guardando.value = false
      }
    }

    const editarEmpleado = (empleado) => {
      editando.value = true
      empleadoEditId.value = empleado.id_empleado

      form.value = {
        nombre: empleado.nombre || '',
        cargo: empleado.cargo || '',
        telefono: empleado.telefono || '',
        fecha_nacimiento: empleado.fecha_nacimiento || '',
        contacto_referencia: empleado.contacto_referencia || '',
        telefono_referencia: empleado.telefono_referencia || '',
        usuario: empleado.usuario?.usuario || '',
        email: empleado.usuario?.email || '',
        password: '',
      }

      verPassword.value = false
      modal.value = true
    }

    const verEmpleado = (empleado) => {
      empleadoDetalle.value = empleado
      modalDetalle.value = true
    }

    const cambiarEstado = async (empleado) => {
      const estadoAnterior = empleado.estado

      empleado.estado = empleado.estado === 'ACTIVO' ? 'INACTIVO' : 'ACTIVO'

      try {
        await api.patch(`/empleados/${empleado.id_empleado}/estado`)

        q.notify({
          type: 'positive',
          message: 'Estado actualizado',
        })
      } catch (error) {
        empleado.estado = estadoAnterior

        q.notify({
          type: 'negative',
          message: error.response?.data?.message || 'Error al cambiar estado',
        })
      }
    }

    onMounted(() => {
      cargarEmpleados()
    })

    return {
      empleados,
      filtro,
      loading,
      guardando,
      modal,
      modalDetalle,
      editando,
      empleadoEditId,
      empleadoDetalle,
      verPassword,
      cargos,
      columns,
      form,
      inicialesEmpleado,
      agregarCargo,
      normalizarTexto,
      esCargoCajero,
      esCajeroConUsuario,
      abrirPermisosCajero,
      limpiarForm,
      cargarEmpleados,
      abrirModalCrear,
      guardarEmpleado,
      editarEmpleado,
      verEmpleado,
      cambiarEstado,
    }
  },
})