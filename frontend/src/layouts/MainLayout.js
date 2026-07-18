import { computed, onMounted, ref } from 'vue'
import { useQuasar } from 'quasar'
import { useAuthStore } from '@/stores/auth'
import { useAutoLogout } from '@/composables/useAutoLogout'
import api from '@/api/axios'

const menuItems = [
  // SUPERADMIN
  {
    label: 'Dashboard',
    caption: 'Resumen general de ventas',
    icon: 'dashboard',
    to: '/superadmin/dashboard',
    roles: ['SUPERADMIN'],
  },
  {
    label: 'Sucursales',
    caption: 'Gestión de sucursales',
    icon: 'store',
    to: '/superadmin/sucursales',
    roles: ['SUPERADMIN'],
    permiso: 'ver_sucursales',
  },
  {
    label: 'Administradores',
    caption: 'Gestión de administradores',
    icon: 'admin_panel_settings',
    to: '/superadmin/administradores',
    roles: ['SUPERADMIN'],
    permiso: 'ver_administradores',
  },
  {
    label: 'Reportes',
    caption: 'Cierres de todas las sucursales',
    icon: 'assessment',
    to: '/superadmin/reportes-jornada',
    roles: ['SUPERADMIN'],
    permiso: 'ver_reportes',
  },
  {
    label: 'Solicitudes',
    caption: 'Requerimientos de sucursales',
    icon: 'inbox',
    to: '/superadmin/solicitudes',
    roles: ['SUPERADMIN'],
    permiso: 'ver_solicitudes',
  },

  // ADMIN
  {
    label: 'Dashboard',
    caption: 'Estadísticas de la sucursal',
    icon: 'dashboard',
    to: '/admin/dashboard',
    roles: ['ADMIN'],
  },
  {
    label: 'Inventario',
    caption: 'Control de productos y stock',
    icon: 'inventory_2',
    to: '/admin/inventario',
    roles: ['ADMIN'],
    permiso: 'ver_inventario',
  },
  {
    label: 'Empleados',
    caption: 'Gestión del personal',
    icon: 'groups',
    to: '/admin/empleados',
    roles: ['ADMIN'],
    permiso: 'ver_empleados',
  },
  {
    label: 'Jornadas',
    caption: 'Apertura y cierre del día',
    icon: 'event_available',
    to: '/admin/jornadas',
    roles: ['ADMIN'],
    permiso: 'ver_jornadas',
  },
  {
    label: 'Catálogo',
    caption: 'Administrar platos y bebidas',
    icon: 'restaurant_menu',
    to: '/admin/catalogo-productos',
    roles: ['ADMIN'],
    permiso: 'ver_catalogo_pedidos',
  },
  {
    label: 'Cajas',
    caption: 'Control de cajas y cajeros',
    icon: 'point_of_sale',
    to: '/admin/cajas',
    roles: ['ADMIN'],
    permiso: 'ver_cajas',
  },
  {
    label: 'Compras internas',
    caption: 'Gastos operativos de la jornada',
    icon: 'shopping_cart_checkout',
    to: '/admin/compras-internas',
    roles: ['ADMIN'],
    permiso: 'ver_compras_internas',
  },
  {
    label: 'Movimientos de carne',
    caption: 'Llegadas, salidas y ajustes',
    icon: 'set_meal',
    to: '/admin/movimientos-carne',
    roles: ['ADMIN'],
    permiso: 'ver_movimientos_carne',
  },
  {
    label: 'Reportes',
    caption: 'Informes de cierre de jornada',
    icon: 'bar_chart',
    to: '/admin/reportes',
    roles: ['ADMIN'],
    permiso: 'ver_reportes_jornada',
  },
  {
    label: 'Solicitudes',
    caption: 'Solicitudes al superadmin',
    icon: 'outgoing_mail',
    to: '/admin/solicitudes',
    roles: ['ADMIN'],
    permiso: 'crear_solicitudes',
  },
  {
    label: 'Pantallas',
    caption: 'Configurar áreas y finalización',
    icon: 'desktop_windows',
    to: '/admin/pantallas',
    roles: ['ADMIN'],
    permiso: 'ver_pantallas',
  },

  // CAJERO
  {
    label: 'Dashboard',
    caption: 'Panel de caja',
    icon: 'dashboard',
    to: '/cajero/dashboard',
    roles: ['CAJERO'],
  },
  {
    label: 'Nuevo Pedido',
    caption: 'Registrar venta o pedido',
    icon: 'point_of_sale',
    to: '/cajero/nuevo-pedido',
    roles: ['CAJERO'],
    permiso: 'crear_pedidos',
  },
  {
    label: 'Registrar Pago',
    caption: 'Confirmar pago del cliente',
    icon: 'payments',
    to: '/cajero/registrar-pago',
    roles: ['CAJERO'],
    permiso: 'registrar_pagos',
  },
  {
    label: 'Historial Pedidos',
    caption: 'Pedidos registrados',
    icon: 'receipt_long',
    to: '/cajero/historial',
    roles: ['CAJERO'],
    permiso: 'ver_pedidos',
  },
  {
    label: 'Imprimir Ticket',
    caption: 'Volver a imprimir comprobante',
    icon: 'print',
    to: '/cajero/reimprimir-ticket',
    roles: ['CAJERO'],
    permiso: 'reimprimir_ticket',
  },
  {
    label: 'Mi caja',
    caption: 'Abrir, cerrar y controlar caja',
    icon: 'point_of_sale',
    to: '/cajero/caja',
    roles: ['CAJERO'],
    permiso: 'ver_cajas',
  },
  {
    label: 'Stock Bebidas',
    caption: 'Entradas y salidas de bebidas',
    icon: 'local_drink',
    to: '/cajero/stock-bebidas',
    roles: ['CAJERO'],
    permiso: 'ver_stock_bebidas',
  },
]

export function useMainLayout() {
  const $q = useQuasar()
  const authStore = useAuthStore()

  const drawer = ref(true)
  const mini = ref(false)
  const sucursales = ref([])
  const cargandoSucursales = ref(false)

  useAutoLogout()

  const miniSidebar = computed(() => {
    return $q.screen.gt.sm && mini.value
  })

  const usuario = computed(() => authStore.user || null)

  const nombreUsuario = computed(() => {
    return usuario.value?.name || usuario.value?.nombre || 'Usuario'
  })

  const rolUsuario = computed(() => {
    return authStore.rol || usuario.value?.rol || usuario.value?.nombre_rol || 'SIN ROL'
  })

  const tienePermisoItem = (item) => {
    if (!item.permiso) {
      return true
    }

    return authStore.tienePermiso(item.permiso)
  }

  const menuSucursales = computed(() => {
    if (rolUsuario.value !== 'SUPERADMIN' || !authStore.tienePermiso('ver_sucursales')) {
      return []
    }

    const items = sucursales.value.map((sucursal) => ({
      label: sucursal.nombre,
      caption: sucursal.estado === 'ACTIVA' ? 'Ver estadísticas' : 'Sucursal inactiva',
      icon: sucursal.estado === 'ACTIVA' ? 'storefront' : 'store_mall_directory',
      to: `/superadmin/sucursales/${sucursal.id_sucursal}/estadisticas`,
      roles: ['SUPERADMIN'],
      estado: sucursal.estado,
    }))

    if (items.length === 0) {
      return []
    }

    return [
      {
        label: 'Paneles de sucursales',
        esTitulo: true,
      },
      ...items,
    ]
  })

  const menuFiltrado = computed(() => {
    const menuBase = menuItems.filter((item) => {
      const rolCorrecto = item.roles?.includes(rolUsuario.value)
      const permisoCorrecto = tienePermisoItem(item)

      return rolCorrecto && permisoCorrecto
    })

    if (rolUsuario.value !== 'SUPERADMIN') {
      return menuBase
    }

    const indiceSucursales = menuBase.findIndex((item) => item.to === '/superadmin/sucursales')

    if (indiceSucursales === -1) {
      return [...menuBase, ...menuSucursales.value]
    }

    return [
      ...menuBase.slice(0, indiceSucursales + 1),
      ...menuSucursales.value,
      ...menuBase.slice(indiceSucursales + 1),
    ]
  })

  const alternarSidebar = () => {
    if ($q.screen.gt.sm) {
      drawer.value = true
      mini.value = !mini.value
      return
    }

    drawer.value = !drawer.value
  }

  const cargarSucursalesSidebar = async () => {
    if (rolUsuario.value !== 'SUPERADMIN' || !authStore.tienePermiso('ver_sucursales')) {
      sucursales.value = []
      return
    }

    cargandoSucursales.value = true

    try {
      const response = await api.get('/sucursales')

      sucursales.value = Array.isArray(response.data?.sucursales) ? response.data.sucursales : []
    } catch (error) {
      console.error('Error al cargar sucursales del sidebar:', error)
      sucursales.value = []
    } finally {
      cargandoSucursales.value = false
    }
  }

  const cambiarModoOscuro = () => {
    $q.dark.toggle()

    $q.notify({
      type: 'info',
      message: $q.dark.isActive ? 'Modo oscuro activado' : 'Modo claro activado',
      position: 'top',
      timeout: 1500,
    })
  }

  const cerrarSesion = async () => {
    await authStore.logout()

    $q.notify({
      type: 'positive',
      message: 'Sesión cerrada correctamente',
      position: 'top',
      timeout: 2000,
    })
  }

  onMounted(async () => {
    authStore.escucharPermisosTiempoReal()
    await cargarSucursalesSidebar()
  })

  return {
    drawer,
    miniSidebar,
    nombreUsuario,
    rolUsuario,
    menuFiltrado,
    cargandoSucursales,
    alternarSidebar,
    cambiarModoOscuro,
    cerrarSesion,
  }
}
