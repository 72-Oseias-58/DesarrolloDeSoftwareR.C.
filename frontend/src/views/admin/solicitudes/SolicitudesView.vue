<template>
  <div class="admin-module-page solicitudes-page">
    <q-card class="admin-module-card">
      <q-card-section>
        <div class="row items-center justify-between q-gutter-md">
          <div>
            <div class="text-h5 text-weight-bold">Solicitudes</div>
            <div class="text-grey-7">
              Creación de solicitudes administrativas para soporte, inventario o gestión interna.
            </div>
          </div>

          <q-icon name="outgoing_mail" size="52px" color="primary" />
        </div>
      </q-card-section>

      <q-separator />

      <q-card-section>
        <div class="row q-col-gutter-md">
          <div class="col-12 col-md-4">
            <q-card flat bordered class="admin-option-card">
              <q-card-section>
                <q-icon name="add_comment" size="34px" color="primary" />
                <div class="text-subtitle1 text-weight-bold q-mt-sm">Nueva solicitud</div>
                <div class="text-grey-7">
                  El ADMIN podrá crear solicitudes para el SUPERADMIN.
                </div>
              </q-card-section>
            </q-card>
          </div>

          <div class="col-12 col-md-4">
            <q-card flat bordered class="admin-option-card">
              <q-card-section>
                <q-icon name="inventory_2" size="34px" color="orange" />
                <div class="text-subtitle1 text-weight-bold q-mt-sm">Inventario</div>
                <div class="text-grey-7">
                  Solicitudes relacionadas con stock, insumos o recursos de la sucursal.
                </div>
              </q-card-section>
            </q-card>
          </div>

          <div class="col-12 col-md-4">
            <q-card flat bordered class="admin-option-card">
              <q-card-section>
                <q-icon name="support_agent" size="34px" color="green" />
                <div class="text-subtitle1 text-weight-bold q-mt-sm">Soporte</div>
                <div class="text-grey-7">
                  Solicitudes técnicas o administrativas para revisión superior.
                </div>
              </q-card-section>
            </q-card>
          </div>
        </div>

        <q-banner rounded class="bg-purple-1 text-purple-10 q-mt-md">
          Esta vista se conectará después al backend de solicitudes.
        </q-banner>
      </q-card-section>
    </q-card>
  </div>
</template>

<script src="./SolicitudesView.js"></script>

<style src="./SolicitudesView.css" scoped></style>