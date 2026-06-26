import {
  computed,
  defineComponent,
  onMounted,
  ref,
} from 'vue'

import {
  useRoute,
  useRouter,
} from 'vue-router'

import { useQuasar } from 'quasar'
import { permisosService } from '@/services/permisosService'

export default defineComponent({
  name: 'PermisosUsuarioView',

  setup() {
    const route = useRoute()
    const router = useRouter()
    const $q = useQuasar()

    const loading = ref(false)
    const guardando = ref(false)

    const usuario = ref(null)
    const catalogoPermisos = ref([])
    const permisosBaseRol = ref([])
    const permisosFinalesOriginales = ref([])
    const permisosSeleccionados = ref([])

    const columns = [
      {
        name: 'modulo',
        label: 'Módulo',
        field: 'modulo',
        align: 'left',
        style: 'width: 260px',
      },
      {
        name: 'permisos',
        label: 'Permisos',
        field: 'permisos',
        align: 'left',
      },
    ]

    const gruposPermitidosPorRol = {
      ADMIN: [
        'Empleados',
        'Cajeros',
        'Inventario',
        'Jornadas',
        'Cajas',
        'Reportes',
        'Solicitudes',
        'Permisos',
      ],

      CAJERO: [
        'Cajas',
        'Pedidos',
        'Pagos',
        'Tickets',
      ],
    }

    const nombreRolUsuario = computed(() => {
      return (
        usuario.value?.role?.nombre_rol ||
        usuario.value?.rol?.nombre_rol ||
        usuario.value?.role?.nombre ||
        usuario.value?.rol?.nombre ||
        'SIN ROL'
      )
    })

    const nombreSucursalUsuario = computed(() => {
      const sucursal =
        usuario.value?.empleado?.sucursal

      if (!sucursal) {
        return 'Sin sucursal'
      }

      if (typeof sucursal === 'string') {
        return sucursal
      }

      return sucursal.nombre || 'Sin sucursal'
    })

    const obtenerModuloPermiso = (slug = '') => {
      const slugNormalizado = String(slug).toLowerCase()

      if (slugNormalizado.includes('sucursal')) {
        return 'Sucursales'
      }

      if (slugNormalizado.includes('administrador')) {
        return 'Administradores'
      }

      if (slugNormalizado.includes('empleado')) {
        return 'Empleados'
      }

      if (slugNormalizado.includes('cajero')) {
        return 'Cajeros'
      }

      if (slugNormalizado.includes('inventario')) {
        return 'Inventario'
      }

      if (slugNormalizado.includes('jornada')) {
        return 'Jornadas'
      }

      if (slugNormalizado.includes('caja')) {
        return 'Cajas'
      }

      if (slugNormalizado.includes('pedido')) {
        return 'Pedidos'
      }

      if (slugNormalizado.includes('pago')) {
        return 'Pagos'
      }

      if (slugNormalizado.includes('ticket')) {
        return 'Tickets'
      }

      if (slugNormalizado.includes('reporte')) {
        return 'Reportes'
      }

      if (slugNormalizado.includes('solicitud')) {
        return 'Solicitudes'
      }

      if (slugNormalizado.includes('permiso')) {
        return 'Permisos'
      }

      return 'Otros'
    }

    const permisosFiltradosPorRol = computed(() => {
      const rolObjetivo =
        String(nombreRolUsuario.value)
          .trim()
          .toUpperCase()

      const gruposPermitidos =
        gruposPermitidosPorRol[rolObjetivo] || []

      return catalogoPermisos.value.filter(
        (permiso) => {
          const modulo = obtenerModuloPermiso(
            permiso.slug,
          )

          return gruposPermitidos.includes(modulo)
        },
      )
    })

    const permisosAgrupados = computed(() => {
      const grupos = {}

      permisosFiltradosPorRol.value.forEach(
        (permiso) => {
          const modulo = obtenerModuloPermiso(
            permiso.slug,
          )

          if (!grupos[modulo]) {
            grupos[modulo] = []
          }

          grupos[modulo].push(permiso)
        },
      )

      return Object.entries(grupos)
        .map(([modulo, permisos]) => ({
          modulo,
          permisos: permisos.sort((a, b) =>
            String(a.nombre || a.slug).localeCompare(
              String(b.nombre || b.slug),
              'es',
            ),
          ),
        }))
        .sort((a, b) =>
          a.modulo.localeCompare(b.modulo, 'es'),
        )
    })

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

    const volver = () => {
      const rolObjetivo =
        String(nombreRolUsuario.value)
          .trim()
          .toUpperCase()

      if (rolObjetivo === 'ADMIN') {
        router.push('/superadmin/administradores')
        return
      }

      if (rolObjetivo === 'CAJERO') {
        router.push('/admin/empleados')
        return
      }

      router.back()
    }

    const cargarPermisos = async () => {
      const idUsuario = Number(route.params.id)

      if (
        !Number.isInteger(idUsuario) ||
        idUsuario <= 0
      ) {
        $q.notify({
          type: 'negative',
          message:
            'El usuario seleccionado no es válido.',
          position: 'top',
        })

        volver()
        return
      }

      loading.value = true

      try {
        const data =
          await permisosService.obtenerPermisosUsuario(
            idUsuario,
          )

        usuario.value = data?.usuario || null

        catalogoPermisos.value =
          Array.isArray(data?.catalogo_permisos)
            ? data.catalogo_permisos
            : []

        permisosBaseRol.value =
          Array.isArray(data?.permisos_base_rol)
            ? data.permisos_base_rol
            : []

        permisosFinalesOriginales.value =
          Array.isArray(data?.permisos_finales)
            ? data.permisos_finales
            : []

        permisosSeleccionados.value = [
          ...permisosFinalesOriginales.value,
        ]
      } catch (error) {
        console.error(
          'Error al cargar permisos:',
          error,
        )

        $q.notify({
          type: 'negative',
          message: obtenerMensajeError(
            error,
            'Error al cargar permisos.',
          ),
          position: 'top',
        })

        volver()
      } finally {
        loading.value = false
      }
    }

    const guardarPermisos = async () => {
      const idUsuario = Number(route.params.id)

      if (
        !Number.isInteger(idUsuario) ||
        idUsuario <= 0
      ) {
        $q.notify({
          type: 'negative',
          message:
            'El usuario seleccionado no es válido.',
          position: 'top',
        })

        return
      }

      guardando.value = true

      try {
        const base = permisosBaseRol.value
        const seleccionados =
          permisosSeleccionados.value

        const agregados = seleccionados.filter(
          (slug) => !base.includes(slug),
        )

        const quitados = base.filter(
          (slug) => !seleccionados.includes(slug),
        )

        await permisosService.actualizarPermisosUsuario(
          idUsuario,
          {
            agregados,
            quitados,
          },
        )

        $q.notify({
          type: 'positive',
          message:
            'Permisos actualizados correctamente.',
          position: 'top',
        })

        await cargarPermisos()
      } catch (error) {
        console.error(
          'Error al guardar permisos:',
          error,
        )

        $q.notify({
          type: 'negative',
          message: obtenerMensajeError(
            error,
            'Error al guardar permisos.',
          ),
          position: 'top',
        })
      } finally {
        guardando.value = false
      }
    }

    onMounted(async () => {
      await cargarPermisos()
    })

    return {
      loading,
      guardando,

      usuario,
      catalogoPermisos,
      permisosBaseRol,
      permisosFinalesOriginales,
      permisosSeleccionados,

      columns,
      nombreRolUsuario,
      nombreSucursalUsuario,
      permisosAgrupados,

      cargarPermisos,
      guardarPermisos,
      volver,
    }
  },
})