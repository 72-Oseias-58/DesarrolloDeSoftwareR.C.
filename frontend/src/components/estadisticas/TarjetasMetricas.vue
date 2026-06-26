<template>
  <div class="row q-col-gutter-md">
    <div
      v-for="tarjeta in tarjetas"
      :key="tarjeta.clave"
      class="col-12 col-sm-6 col-lg"
    >
      <q-card class="metric-card">
        <q-card-section class="row items-center no-wrap">
          <q-avatar
            :icon="tarjeta.icono"
            :color="tarjeta.color"
            text-color="white"
            size="52px"
          />

          <div class="q-ml-md">
            <div class="text-caption text-grey-7">
              {{ tarjeta.titulo }}
            </div>

            <div class="text-h6 text-weight-bold q-mt-xs">
              <q-skeleton
                v-if="cargando"
                type="text"
                width="110px"
              />

              <span v-else>
                {{ tarjeta.valor }}
              </span>
            </div>
          </div>
        </q-card-section>
      </q-card>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  metricas: {
    type: Object,
    default: () => ({
      total_ventas: 0,
      cantidad_pedidos: 0,
      ticket_promedio: 0,
      total_efectivo: 0,
      total_qr: 0,
    }),
  },

  cargando: {
    type: Boolean,
    default: false,
  },
})

const formatearMoneda = (valor) => {
  return new Intl.NumberFormat('es-BO', {
    style: 'currency',
    currency: 'BOB',
    minimumFractionDigits: 2,
  }).format(Number(valor || 0))
}

const formatearNumero = (valor) => {
  return new Intl.NumberFormat('es-BO').format(
    Number(valor || 0),
  )
}

const tarjetas = computed(() => [
  {
    clave: 'total_ventas',
    titulo: 'Ventas totales',
    valor: formatearMoneda(props.metricas.total_ventas),
    icono: 'paid',
    color: 'primary',
  },
  {
    clave: 'cantidad_pedidos',
    titulo: 'Pedidos pagados',
    valor: formatearNumero(props.metricas.cantidad_pedidos),
    icono: 'receipt_long',
    color: 'deep-orange',
  },
  {
    clave: 'ticket_promedio',
    titulo: 'Ticket promedio',
    valor: formatearMoneda(props.metricas.ticket_promedio),
    icono: 'monitoring',
    color: 'purple',
  },
  {
    clave: 'total_efectivo',
    titulo: 'Total efectivo',
    valor: formatearMoneda(props.metricas.total_efectivo),
    icono: 'payments',
    color: 'green',
  },
  {
    clave: 'total_qr',
    titulo: 'Total QR',
    valor: formatearMoneda(props.metricas.total_qr),
    icono: 'qr_code_2',
    color: 'blue',
  },
])
</script>

<style scoped>
.metric-card {
  border-radius: 16px;
  height: 100%;
  transition:
    transform 0.2s ease,
    box-shadow 0.2s ease;
}

.metric-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 22px rgb(0 0 0 / 10%);
}
</style>