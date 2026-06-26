import {
  computed,
  defineComponent,
  onMounted,
  ref,
} from 'vue'

import { useQuasar } from 'quasar'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import api from '@/api/axios'

const crearFormularioInicial = () => ({
  name: '',
  usuario: '',
  email: '',
  password: '',
  id_sucursal: null,
  fecha_nacimiento: '',
  telefono: '',
  contacto_referencia: '',
  telefono_referencia: '',
})

export default defineComponent({
  name: 'AdministradoresView',

  setup() {
    const $q = useQuasar()
    const router = useRouter()
    const authStore = useAuthStore()

    const tab = ref('administradores')

    const usuariosGestionables = ref([])
    const sucursales = ref([])

    const loading = ref(false)
    const guardando = ref(false)
    const cambiandoRol = ref(false)
    const cambiandoRolId = ref(null)

    const modal = ref(false)
    const modalDetalles = ref(false)
    const editando = ref(false)

    const adminEditId = ref(null)
    const adminDetalle = ref(null)
    const verPassword = ref(false)

    const form = ref(crearFormularioInicial())

    const columns = [
      {
        name: 'name',
        label: 'Nombre',
        field: 'name',
        align: 'left',
      },
      {
        name: 'usuario',
        label: 'Usuario',
        field: 'usuario',
        align: 'left',
      },
      {
        name: 'rol',
        label: 'Rol',
        field: 'rol',
        align: 'center',
      },
      {
        name: 'email',
        label: 'Correo',
        field: (row) => row.email || 'Sin correo',
        align: 'left',
      },
      {
        name: 'sucursal',
        label: 'Sucursal',
        field: 'sucursal',
        align: 'left',
      },
      {
        name: 'estado',
        label: 'Estado',
        field: 'estado',
        align: 'center',
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
        error.response?.data?.error ||
        error.response?.data?.message ||
        mensajePredeterminado
      )
    }

    const obtenerNombreRol = (usuario) => {
      return String(
        usuario?.rol?.nombre ||
          usuario?.rol?.nombre_rol ||
          usuario?.role?.nombre_rol ||
          usuario?.role?.nombre ||
          '',
      )
        .trim()
        .toUpperCase()
    }

    const administradores = computed(() => {
      return usuariosGestionables.value.filter(
        (usuario) =>
          obtenerNombreRol(usuario) === 'ADMIN',
      )
    })

    const cajeros = computed(() => {
      return usuariosGestionables.value.filter(
        (usuario) =>
          obtenerNombreRol(usuario) === 'CAJERO',
      )
    })

    const sucursalesActivas = computed(() => {
      return sucursales.value.filter(
        (sucursal) =>
          sucursal.estado === 'ACTIVA',
      )
    })

    const limpiarForm = () => {
      form.value = crearFormularioInicial()

      adminEditId.value = null
      editando.value = false
      verPassword.value = false
    }

    const cargarUsuariosGestionables = async () => {
      loading.value = true

      try {
        const response = await api.get(
          '/superadmin/usuarios-gestionables',
        )

        usuariosGestionables.value =
          Array.isArray(response.data?.usuarios)
            ? response.data.usuarios
            : []
      } catch (error) {
        console.error(
          'Error al cargar usuarios gestionables:',
          error,
        )

        $q.notify({
          type: 'negative',
          message: obtenerMensajeError(
            error,
            'Error al cargar administradores y cajeros.',
          ),
          position: 'top',
        })
      } finally {
        loading.value = false
      }
    }

    const cargarSucursales = async () => {
      try {
        const response = await api.get('/sucursales')

        sucursales.value =
          Array.isArray(response.data?.sucursales)
            ? response.data.sucursales
            : []
      } catch (error) {
        console.error(
          'Error al cargar sucursales:',
          error,
        )

        $q.notify({
          type: 'negative',
          message: obtenerMensajeError(
            error,
            'Error al cargar sucursales.',
          ),
          position: 'top',
        })
      }
    }

    const abrirModalCrear = () => {
      limpiarForm()
      modal.value = true
    }

    const abrirModalEditar = (admin) => {
      editando.value = true
      adminEditId.value = admin.id

      form.value = {
        name: admin.name || '',
        usuario: admin.usuario || '',
        email: admin.email || '',
        password: '',

        id_sucursal:
          admin.empleado?.id_sucursal ||
          admin.empleado?.sucursal
            ?.id_sucursal ||
          null,

        fecha_nacimiento:
          admin.empleado?.fecha_nacimiento || '',

        telefono:
          admin.empleado?.telefono || '',

        contacto_referencia:
          admin.empleado?.contacto_referencia ||
          '',

        telefono_referencia:
          admin.empleado?.telefono_referencia ||
          '',
      }

      verPassword.value = false
      modal.value = true
    }

    const abrirDetalles = (usuario) => {
      adminDetalle.value = usuario
      modalDetalles.value = true
    }

    const abrirPermisos = (admin) => {
      if (!admin?.id) {
        $q.notify({
          type: 'negative',
          message:
            'No se encontró el usuario del administrador.',
          position: 'top',
        })

        return
      }

      router.push(
        `/superadmin/usuarios/${admin.id}/permisos`,
      )
    }

    const guardarAdministrador = async () => {
      if (!form.value.name?.trim()) {
        $q.notify({
          type: 'warning',
          message:
            'El nombre del administrador es obligatorio.',
          position: 'top',
        })

        return
      }

      if (!form.value.usuario?.trim()) {
        $q.notify({
          type: 'warning',
          message: 'El usuario es obligatorio.',
          position: 'top',
        })

        return
      }

      if (
        !editando.value &&
        !form.value.password
      ) {
        $q.notify({
          type: 'warning',
          message:
            'La contraseña es obligatoria.',
          position: 'top',
        })

        return
      }

      if (!form.value.id_sucursal) {
        $q.notify({
          type: 'warning',
          message:
            'Debe seleccionar una sucursal.',
          position: 'top',
        })

        return
      }

      guardando.value = true

      try {
        const payload = {
          ...form.value,
          name: form.value.name.trim(),
          usuario: form.value.usuario.trim(),
          email:
            form.value.email?.trim() || null,
        }

        if (
          editando.value &&
          !payload.password
        ) {
          delete payload.password
        }

        if (editando.value) {
          await api.put(
            `/administradores/${adminEditId.value}`,
            payload,
          )

          $q.notify({
            type: 'positive',
            message:
              'Administrador actualizado correctamente.',
            position: 'top',
          })
        } else {
          await api.post(
            '/administradores',
            payload,
          )

          $q.notify({
            type: 'positive',
            message:
              'Administrador creado correctamente.',
            position: 'top',
          })
        }

        modal.value = false
        limpiarForm()

        await cargarUsuariosGestionables()
      } catch (error) {
        console.error(
          'Error al guardar administrador:',
          error,
        )

        $q.notify({
          type: 'negative',
          message: obtenerMensajeError(
            error,
            'Error al guardar administrador.',
          ),
          position: 'top',
        })
      } finally {
        guardando.value = false
      }
    }

    const cambiarEstado = async (admin) => {
      if (!admin.empleado) {
        $q.notify({
          type: 'negative',
          message:
            'Este administrador no tiene empleado registrado.',
          position: 'top',
        })

        return
      }

      const estadoAnterior =
        admin.empleado.estado

      admin.empleado.estado =
        estadoAnterior === 'ACTIVO'
          ? 'INACTIVO'
          : 'ACTIVO'

      try {
        const response = await api.patch(
          `/administradores/${admin.id}/estado`,
        )

        if (response.data?.administrador) {
          const indice =
            usuariosGestionables.value.findIndex(
              (usuario) =>
                usuario.id === admin.id,
            )

          if (indice !== -1) {
            usuariosGestionables.value[indice] =
              response.data.administrador
          }
        }

        $q.notify({
          type: 'positive',
          message: 'Estado actualizado.',
          position: 'top',
        })
      } catch (error) {
        admin.empleado.estado =
          estadoAnterior

        $q.notify({
          type: 'negative',
          message: obtenerMensajeError(
            error,
            'Error al cambiar estado.',
          ),
          position: 'top',
        })
      }
    }

    const cambiarRol = async (
      usuario,
      nuevoRol,
    ) => {
      if (cambiandoRol.value) {
        return
      }

      cambiandoRol.value = true
      cambiandoRolId.value = usuario.id

      try {
        const response = await api.patch(
          `/usuarios/${usuario.id}/rol`,
          {
            rol: nuevoRol,
          },
        )

        $q.notify({
          type: 'positive',
          message:
            response.data?.message ||
            'Rol actualizado correctamente.',
          position: 'top',
        })

        await cargarUsuariosGestionables()

        tab.value =
          nuevoRol === 'ADMIN'
            ? 'administradores'
            : 'cajeros'
      } catch (error) {
        console.error(
          'Error al cambiar rol:',
          error,
        )

        $q.notify({
          type: 'negative',
          message: obtenerMensajeError(
            error,
            'No se pudo cambiar el rol.',
          ),
          position: 'top',
        })
      } finally {
        cambiandoRol.value = false
        cambiandoRolId.value = null
      }
    }

    const confirmarDegradacion = (admin) => {
      $q.dialog({
        title: 'Degradar administrador',

        message:
          `¿Deseas degradar a ${admin.name} al rol CAJERO? ` +
          'Sus permisos personalizados de ADMIN serán eliminados.',

        ok: {
          label: 'Degradar',
          color: 'orange',
          unelevated: true,
        },

        cancel: {
          label: 'Cancelar',
          flat: true,
        },

        persistent: true,
      }).onOk(() => {
        cambiarRol(admin, 'CAJERO')
      })
    }

    const confirmarAscenso = (cajero) => {
      $q.dialog({
        title: 'Ascender cajero',

        message:
          `¿Deseas ascender a ${cajero.name} al rol ADMIN? ` +
          'Recibirá los permisos base de administrador.',

        ok: {
          label: 'Ascender',
          color: 'green',
          unelevated: true,
        },

        cancel: {
          label: 'Cancelar',
          flat: true,
        },

        persistent: true,
      }).onOk(() => {
        cambiarRol(cajero, 'ADMIN')
      })
    }

    onMounted(async () => {
      await Promise.all([
        cargarUsuariosGestionables(),
        cargarSucursales(),
      ])
    })

    return {
      authStore,

      tab,
      usuariosGestionables,
      sucursales,

      loading,
      guardando,
      cambiandoRol,
      cambiandoRolId,

      modal,
      modalDetalles,
      editando,

      adminEditId,
      adminDetalle,
      verPassword,

      form,
      columns,

      administradores,
      cajeros,
      sucursalesActivas,

      obtenerNombreRol,
      cargarUsuariosGestionables,
      cargarSucursales,

      abrirModalCrear,
      abrirModalEditar,
      abrirDetalles,
      abrirPermisos,

      guardarAdministrador,
      cambiarEstado,

      confirmarDegradacion,
      confirmarAscenso,
    }
  },
})