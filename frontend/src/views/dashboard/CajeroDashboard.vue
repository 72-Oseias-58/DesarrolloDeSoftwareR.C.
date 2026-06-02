<template>
  <main class="dashboard-page q-pa-lg" :class="{ 'dashboard-page-dark': $q.dark.isActive }">
    <div class="row items-center justify-between q-mb-lg">
      <div>
        <div class="text-h4 text-weight-bold text-dark">
          Dashboard CAJERO
        </div>

        <div class="text-subtitle1 text-grey-7 q-mt-xs">
          Panel de caja para registrar pedidos, pagos y tickets.
        </div>
      </div>

      <div class="row q-gutter-sm">
        <q-btn
          color="secondary"
          text-color="dark"
          icon="dark_mode"
          label="Modo oscuro"
          unelevated
          rounded
          @click="cambiarModoOscuro"
        />

        <q-btn
          color="negative"
          icon="logout"
          label="Cerrar sesión"
          unelevated
          rounded
          @click="cerrarSesion"
        />
      </div>
    </div>

    <div class="row q-col-gutter-lg">
      <div class="col-12 col-md-4">
        <q-card class="dashboard-card">
          <q-card-section>
            <q-icon name="point_of_sale" size="42px" color="primary" />

            <div class="text-h6 text-weight-bold q-mt-md">
              Nueva venta / pedido
            </div>

            <div class="text-grey-7 q-mt-sm">
              Registrar pedidos de clientes y generar el código de atención.
            </div>
          </q-card-section>

          <q-card-actions align="right">
            <q-btn flat color="primary" label="Entrar" icon-right="arrow_forward" />
          </q-card-actions>
        </q-card>
      </div>

      <div class="col-12 col-md-4">
        <q-card class="dashboard-card">
          <q-card-section>
            <q-icon name="payments" size="42px" color="secondary" />

            <div class="text-h6 text-weight-bold q-mt-md">
              Registrar pago
            </div>

            <div class="text-grey-7 q-mt-sm">
              Registrar pagos en efectivo, QR o pagos mixtos del cliente.
            </div>
          </q-card-section>

          <q-card-actions align="right">
            <q-btn flat color="secondary" label="Entrar" icon-right="arrow_forward" />
          </q-card-actions>
        </q-card>
      </div>

      <div class="col-12 col-md-4">
        <q-card class="dashboard-card">
          <q-card-section>
            <q-icon name="receipt_long" size="42px" color="accent" />

            <div class="text-h6 text-weight-bold q-mt-md">
              Reimprimir ticket
            </div>

            <div class="text-grey-7 q-mt-sm">
              Buscar pedidos registrados y volver a imprimir el ticket.
            </div>
          </q-card-section>

          <q-card-actions align="right">
            <q-btn flat color="accent" label="Entrar" icon-right="arrow_forward" />
          </q-card-actions>
        </q-card>
      </div>
    </div>
  </main>
</template>

<script setup>
import { useDashboardActions } from '@/composables/useDashboardActions'

const { $q, cambiarModoOscuro, cerrarSesion } = useDashboardActions()
</script>