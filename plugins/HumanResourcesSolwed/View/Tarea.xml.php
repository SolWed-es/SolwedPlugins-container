<?xml version="1.0" encoding="UTF-8"?>
<view>
    <columns>
        <column name="id" display="none" order="100">
            <widget type="number" fieldname="idtarea" onclick="EditTarea" />
        </column>
        <column name="name" order="110">
            <widget type="text" fieldname="nombre" />
        </column>
        <column name="address" order="120">
            <widget type="text" fieldname="direccion" />
        </column>
        <column name="status" order="130">
            <widget type="select" fieldname="estado">
                <values title="Pendiente">pendiente</values>
                <values title="En Proceso">en_proceso</values>
                <values title="Completada">completada</values>
            </widget>
        </column>
        <column name="date" order="140">
            <widget type="date" fieldname="fecha" />
        </column>
        <column name="responsible" order="150">
            <widget type="text" fieldname="responsable" />
        </column>
    </columns>
</view>