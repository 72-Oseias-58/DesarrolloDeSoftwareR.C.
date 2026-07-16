import {
  computed,
  ref,
} from 'vue'

import { useQuasar } from 'quasar'
import jornadaService from './jornadaService'

export function useCierreJornada({
  jornadaActual,
  cargarJornadaActual,
  cargarHistorial,
}) {
  const q = useQuasar()

  const preparandoCierre = ref(false)
  const procesandoCierre = ref(false)

  const mostrarDialogoCierre = ref(false)
  const mostrarDialogoBloqueo = ref(false)

  const preparacionCierre = ref(null)
  const comprasPendientes = ref([])
  const cajasCierre = ref([])
  const reporteGenerado = ref(null)

  const redondearDinero = (valor) => {
    return Math.round(
      (Number(valor || 0) + Number.EPSILON) * 100,
    ) / 100
  }

  const formatoDinero = (valor) => {
    return Number(valor || 0).toFixed(2)
  }

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

  const totalMontoInicial = computed(() => {
    return cajasCierre.value.reduce(
      (total, caja) =>
        total + Number(caja.monto_inicial || 0),
      0,
    )
  })

  const totalVentasEfectivo = computed(() => {
    return cajasCierre.value.reduce(
      (total, caja) =>
        total + Number(caja.ventas_efectivo || 0),
      0,
    )
  })

  const totalVentasQr = computed(() => {
    return cajasCierre.value.reduce(
      (total, caja) =>
        total + Number(caja.ventas_qr || 0),
      0,
    )
  })

  const totalAntesGastos = computed(() => {
    return cajasCierre.value.reduce(
      (total, caja) =>
        total +
        Number(caja.efectivo_antes_gastos || 0),
      0,
    )
  })

  const totalGastos = computed(() => {
    return cajasCierre.value.reduce(
      (total, caja) =>
        total +
        Number(
          caja.gastos_compras_internas || 0,
        ),
      0,
    )
  })

  const totalEstimado = computed(() => {
    return cajasCierre.value.reduce(
      (total, caja) =>
        total +
        Number(caja.efectivo_estimado || 0),
      0,
    )
  })

  const totalFisico = computed(() => {
    return cajasCierre.value.reduce(
      (total, caja) =>
        total + Number(caja.monto_fisico || 0),
      0,
    )
  })

  const diferenciaTotal = computed(() => {
    return redondearDinero(
      totalFisico.value - totalEstimado.value,
    )
  })

  const calcularDiferenciaCaja = (caja) => {
    return redondearDinero(
      Number(caja?.monto_fisico || 0) -
        Number(caja?.efectivo_estimado || 0),
    )
  }

  const estadoDiferenciaCaja = (caja) => {
    const diferencia =
      calcularDiferenciaCaja(caja)

    if (diferencia < -0.009) {
      return 'FALTANTE'
    }

    if (diferencia > 0.009) {
      return 'SOBRANTE'
    }

    return 'CUADRA'
  }

  const colorDiferenciaCaja = (caja) => {
    const estado = estadoDiferenciaCaja(caja)

    if (estado === 'FALTANTE') {
      return 'negative'
    }

    if (estado === 'SOBRANTE') {
      return 'orange'
    }

    return 'positive'
  }

  const todasCajasContadas = computed(() => {
    return cajasCierre.value.every((caja) => {
      return (
        caja.monto_fisico !== null &&
        caja.monto_fisico !== '' &&
        Number(caja.monto_fisico) >= 0
      )
    })
  })

  const faltanObservaciones = computed(() => {
    return cajasCierre.value.some((caja) => {
      const diferencia =
        calcularDiferenciaCaja(caja)

      return (
        Math.abs(diferencia) > 0.009 &&
        !String(caja.observacion || '').trim()
      )
    })
  })

  const puedeCerrarJornada = computed(() => {
    if (procesandoCierre.value) {
      return false
    }

    if (!todasCajasContadas.value) {
      return false
    }

    if (faltanObservaciones.value) {
      return false
    }

    return true
  })

  const limpiarCierre = () => {
    preparacionCierre.value = null
    comprasPendientes.value = []
    cajasCierre.value = []
  }

  const cerrarDialogoCierre = () => {
    if (procesandoCierre.value) {
      return
    }

    mostrarDialogoCierre.value = false
    limpiarCierre()
  }

  const prepararCierreJornada = async () => {
    preparandoCierre.value = true
    reporteGenerado.value = null

    try {
      const data =
        await jornadaService.prepararCierre()

      const cierre = data?.cierre || {}

      preparacionCierre.value = cierre

      comprasPendientes.value = Array.isArray(
        cierre.compras_pendientes,
      )
        ? cierre.compras_pendientes
        : []

      if (!cierre.puede_cerrar) {
        cajasCierre.value = []
        mostrarDialogoBloqueo.value = true
        return
      }

      const cajas = Array.isArray(
        cierre.cajas_abiertas,
      )
        ? cierre.cajas_abiertas
        : []

      cajasCierre.value = cajas.map((caja) => ({
        ...caja,
        monto_fisico: null,
        observacion: '',
      }))

      if (cajasCierre.value.length === 0) {
        q.dialog({
          title: 'Cerrar jornada',
          message:
            'No existen cajas abiertas. ¿Deseas cerrar la jornada y generar el reporte?',
          persistent: true,
          ok: {
            label: 'Cerrar jornada',
            color: 'negative',
          },
          cancel: {
            label: 'Cancelar',
            flat: true,
          },
        }).onOk(() => {
          cerrarJornada()
        })

        return
      }

      mostrarDialogoCierre.value = true
    } catch (error) {
      q.notify({
        type: 'negative',
        message: obtenerMensajeError(error),
        position: 'top',
        timeout: 4500,
      })
    } finally {
      preparandoCierre.value = false
    }
  }

  const validarCierre = () => {
    for (const caja of cajasCierre.value) {
      if (
        caja.monto_fisico === null ||
        caja.monto_fisico === ''
      ) {
        return (
          `Debes registrar el efectivo físico ` +
          `de la caja #${caja.id_caja}.`
        )
      }

      if (Number(caja.monto_fisico) < 0) {
        return (
          `El efectivo físico de la caja ` +
          `#${caja.id_caja} no puede ser negativo.`
        )
      }

      const diferencia =
        calcularDiferenciaCaja(caja)

      if (
        Math.abs(diferencia) > 0.009 &&
        !String(caja.observacion || '').trim()
      ) {
        return (
          `Debes explicar la diferencia de ` +
          `la caja #${caja.id_caja}.`
        )
      }
    }

    return null
  }

  const construirPayload = () => {
    return {
      cajas: cajasCierre.value.map((caja) => ({
        id_caja: Number(caja.id_caja),

        monto_fisico: redondearDinero(
          caja.monto_fisico,
        ),

        observacion:
          String(caja.observacion || '').trim() ||
          null,
      })),
    }
  }

  const cerrarJornada = async () => {
    const errorValidacion = validarCierre()

    if (errorValidacion) {
      q.notify({
        type: 'negative',
        message: errorValidacion,
        position: 'top',
        timeout: 4000,
      })

      return
    }

    procesandoCierre.value = true

    try {
      const data = await jornadaService.cerrar(
        construirPayload(),
      )

      jornadaActual.value =
        data?.jornada || null

      reporteGenerado.value =
        data?.reporte || null

      mostrarDialogoCierre.value = false
      limpiarCierre()

      q.notify({
        type: 'positive',
        message:
          data?.message ||
          'Jornada cerrada y reporte generado correctamente.',
        position: 'top',
        timeout: 4500,
      })

      await Promise.all([
        cargarJornadaActual(),
        cargarHistorial(),
      ])
    } catch (error) {
      q.notify({
        type: 'negative',
        message: obtenerMensajeError(error),
        position: 'top',
        timeout: 5000,
      })
    } finally {
      procesandoCierre.value = false
    }
  }

  return {
    preparandoCierre,
    procesandoCierre,

    mostrarDialogoCierre,
    mostrarDialogoBloqueo,

    preparacionCierre,
    comprasPendientes,
    cajasCierre,
    reporteGenerado,

    totalMontoInicial,
    totalVentasEfectivo,
    totalVentasQr,
    totalAntesGastos,
    totalGastos,
    totalEstimado,
    totalFisico,
    diferenciaTotal,

    todasCajasContadas,
    faltanObservaciones,
    puedeCerrarJornada,

    formatoDinero,
    calcularDiferenciaCaja,
    estadoDiferenciaCaja,
    colorDiferenciaCaja,

    prepararCierreJornada,
    cerrarJornada,
    cerrarDialogoCierre,
  }
}