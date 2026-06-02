<template>
  <main class="dashboard-page q-pa-lg" :class="{ 'dashboard-page-dark': $q.dark.isActive }">
    <div class="row items-center justify-between q-mb-lg">
      <div>
        <div class="text-h4 text-weight-bold text-dark">
          Dashboard ADMIN
        </div>

        <div class="text-subtitle1 text-grey-7 q-mt-xs">
          Panel administrativo para la gestión interna de la sucursal.
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
            <q-icon name="inventory_2" size="42px" color="primary" />

            <div class="text-h6 text-weight-bold q-mt-md">
              Inventario
            </div>

            <div class="text-grey-7 q-mt-sm">
              Gestionar productos, insumos, stock y alertas de disponibilidad.
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
            <q-icon name="groups" size="42px" color="secondary" />

            <div class="text-h6 text-weight-bold q-mt-md">
              Empleados
            </div>

            <div class="text-grey-7 q-mt-sm">
              Registrar y administrar cajeros y personal de la sucursal.
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
            <q-icon name="bar_chart" size="42px" color="accent" />

            <div class="text-h6 text-weight-bold q-mt-md">
              Reportes
            </div>

            <div class="text-grey-7 q-mt-sm">
              Consultar reportes de ventas, stock y movimientos diarios.
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