<?php

namespace FacturaScripts\Test\Plugins;

use FacturaScripts\Core\Tools;
use FacturaScripts\Plugins\Vehiculos\Model\Vehiculo;
use FacturaScripts\Plugins\Vehiculos\Model\VehiculoAT;
use FacturaScripts\Plugins\Vehiculos\Model\MaquinaAT;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class VehiculoTest extends TestCase
{
    use LogErrorsTrait;
    use RandomDataTrait;

    public static function setUpBeforeClass(): void
    {
        // Crear un cliente de prueba si no existe
        $cliente = new \FacturaScripts\Dinamic\Model\Cliente();
        if (!$cliente->loadFromCode('TEST001')) {
            $cliente->codcliente = 'TEST001';
            $cliente->nombre = 'Cliente de Prueba';
            $cliente->cifnif = '12345678A';
            $cliente->save();
        }
    }

    /**
     * Test de creación de un vehículo básico
     */
    public function testCreate(): void
    {
        // Crear un vehículo básico
        $vehiculo = new Vehiculo();
        $vehiculo->marca = 'Toyota';
        $vehiculo->modelo = 'Corolla';
        $vehiculo->matricula = 'ABC1234';
        $vehiculo->bastidor = 'JTDBR32E500012345';
        $vehiculo->codcliente = 'TEST001';

        $this->assertTrue($vehiculo->save(), 'Error al guardar el vehículo');
        $this->assertNotNull($vehiculo->idmaquina, 'El ID del vehículo no debería ser null');

        // Verificar que se guardaron los datos correctamente
        $this->assertEquals('TOYOTA', $vehiculo->marca);
        $this->assertEquals('ABC1234', $vehiculo->matricula);
        $this->assertEquals('JTDBR32E500012345', $vehiculo->bastidor);

        // Eliminar el vehículo de prueba
        $this->assertTrue($vehiculo->delete(), 'Error al eliminar el vehículo');
    }

    /**
     * Test de validaciones del modelo
     */
    public function testValidations(): void
    {
        // Test: Vehículo sin cliente debe fallar
        $vehiculo1 = new Vehiculo();
        $vehiculo1->marca = 'Honda';
        $vehiculo1->modelo = 'Civic';
        $vehiculo1->matricula = 'XYZ5678';
        $this->assertFalse($vehiculo1->save(), 'Debería fallar al guardar sin cliente');

        // Test: Vehículo sin modelo debe usar valor por defecto
        $vehiculo2 = new Vehiculo();
        $vehiculo2->matricula = 'DEF9012';
        $vehiculo2->codcliente = 'TEST001';
        $this->assertTrue($vehiculo2->save(), 'Debería guardar con modelo por defecto');
        $this->assertEquals('Sin especificar', $vehiculo2->modelo);
        $vehiculo2->delete();

        // Test: Matrícula duplicada para el mismo cliente debe fallar
        $vehiculo3 = new Vehiculo();
        $vehiculo3->marca = 'Ford';
        $vehiculo3->modelo = 'Focus';
        $vehiculo3->matricula = 'GHI3456';
        $vehiculo3->codcliente = 'TEST001';
        $this->assertTrue($vehiculo3->save(), 'Primer vehículo debería guardarse');

        $vehiculo4 = new Vehiculo();
        $vehiculo4->marca = 'Seat';
        $vehiculo4->modelo = 'Ibiza';
        $vehiculo4->matricula = 'GHI3456'; // Misma matrícula
        $vehiculo4->codcliente = 'TEST001'; // Mismo cliente
        $this->assertFalse($vehiculo4->save(), 'No debería permitir matrícula duplicada para el mismo cliente');

        $vehiculo3->delete();
    }

    /**
     * Test de métodos auxiliares
     */
    public function testAuxiliaryMethods(): void
    {
        $vehiculo = new Vehiculo();
        $vehiculo->marca = 'Volkswagen';
        $vehiculo->modelo = 'Golf';
        $vehiculo->matricula = 'JKL7890';
        $vehiculo->bastidor = 'WVWZZZ1KZBW123456';
        $vehiculo->codcliente = 'TEST001';
        $vehiculo->kilometros = 50000;
        $this->assertTrue($vehiculo->save());

        // Test: referencia()
        $this->assertEquals('WVWZZZ1KZBW123456', $vehiculo->referencia(), 'Debería devolver el bastidor como referencia');

        // Test: getDisplayInfo()
        $displayInfo = $vehiculo->getDisplayInfo();
        $this->assertStringContainsString('Volkswagen', $displayInfo);
        $this->assertStringContainsString('Golf', $displayInfo);
        $this->assertStringContainsString('JKL7890', $displayInfo);

        // Test: generarNombreAutomatico()
        $nombreAuto = $vehiculo->generarNombreAutomatico();
        $this->assertStringContainsString('Volkswagen', $nombreAuto);
        $this->assertStringContainsString('Golf', $nombreAuto);

        // Test: getCliente()
        $cliente = $vehiculo->getCliente();
        $this->assertNotNull($cliente);
        $this->assertEquals('TEST001', $cliente->codcliente);

        $vehiculo->delete();
    }

    /**
     * Test de actualización de kilómetros
     */
    public function testActualizarKilometros(): void
    {
        $vehiculo = new Vehiculo();
        $vehiculo->marca = 'Renault';
        $vehiculo->modelo = 'Clio';
        $vehiculo->matricula = 'MNO1234';
        $vehiculo->codcliente = 'TEST001';
        $vehiculo->kilometros = 10000;
        $this->assertTrue($vehiculo->save());

        // Test: Actualizar a mayor kilometraje
        $this->assertTrue($vehiculo->actualizarKilometros(15000));
        $this->assertEquals(15000, $vehiculo->kilometros);

        // Test: No permitir retroceder sin forzar
        $this->assertTrue($vehiculo->actualizarKilometros(12000, false));
        $this->assertEquals(15000, $vehiculo->kilometros, 'No debería retroceder el kilometraje');

        // Test: Permitir retroceder con forzar
        $this->assertTrue($vehiculo->actualizarKilometros(12000, true));
        $this->assertEquals(12000, $vehiculo->kilometros);

        // Test: No permitir kilometraje negativo
        $this->assertFalse($vehiculo->actualizarKilometros(-100));

        $vehiculo->delete();
    }

    /**
     * Test de compatibilidad de VehiculoAT
     */
    public function testVehiculoATCompatibility(): void
    {
        // VehiculoAT debería ser un alias de Vehiculo
        $vehiculoAT = new VehiculoAT();
        $vehiculoAT->marca = 'Peugeot';
        $vehiculoAT->modelo = '308';
        $vehiculoAT->matricula = 'PQR5678';
        $vehiculoAT->codcliente = 'TEST001';
        $vehiculoAT->color = 'Rojo';
        $vehiculoAT->combustible = 'Diésel';

        $this->assertTrue($vehiculoAT->save(), 'VehiculoAT debería guardar correctamente');
        $this->assertNotNull($vehiculoAT->idmaquina);

        // Test: fecha_matriculacion como alias de fecha_primera_matriculacion
        $vehiculoAT->fecha_matriculacion = '2020-01-15';
        $this->assertEquals('2020-01-15', $vehiculoAT->fecha_primera_matriculacion);
        $this->assertEquals('2020-01-15', $vehiculoAT->fecha_matriculacion);

        $vehiculoAT->delete();
    }

    /**
     * Test de compatibilidad de MaquinaAT
     */
    public function testMaquinaATCompatibility(): void
    {
        // MaquinaAT debería ser un alias de Vehiculo
        $maquinaAT = new MaquinaAT();
        $maquinaAT->marca = 'Citroen';
        $maquinaAT->modelo = 'C4';
        $maquinaAT->matricula = 'STU9012';
        $maquinaAT->codcliente = 'TEST001';
        $maquinaAT->nombre = 'Mi Citroen C4';

        $this->assertTrue($maquinaAT->save(), 'MaquinaAT debería guardar correctamente');
        $this->assertNotNull($maquinaAT->idmaquina);
        $this->assertEquals('Mi Citroen C4', $maquinaAT->nombre);

        $maquinaAT->delete();
    }

    /**
     * Test de campos adicionales
     */
    public function testAdditionalFields(): void
    {
        $vehiculo = new Vehiculo();
        $vehiculo->marca = 'BMW';
        $vehiculo->modelo = 'Serie 3';
        $vehiculo->matricula = 'VWX3456';
        $vehiculo->codcliente = 'TEST001';
        $vehiculo->carroceria = 'Berlina - Tracción trasera';
        $vehiculo->motor = 'N47D20C Motor Diésel Inyección directa';
        $vehiculo->potencia = '2.0d 184CV';
        $vehiculo->color = 'Negro';
        $vehiculo->combustible = 'Diésel';
        $vehiculo->codmotor = 'N47D20C';
        $vehiculo->procedencia_matricula = 'España';
        $vehiculo->fecha_primera_matriculacion = '2019-03-20';

        $this->assertTrue($vehiculo->save());

        // Recargar desde la base de datos
        $vehiculoReloaded = new Vehiculo();
        $this->assertTrue($vehiculoReloaded->loadFromCode($vehiculo->idmaquina));

        $this->assertEquals('Berlina - Tracción trasera', $vehiculoReloaded->carroceria);
        $this->assertEquals('N47D20C Motor Diésel Inyección directa', $vehiculoReloaded->motor);
        $this->assertEquals('2.0d 184CV', $vehiculoReloaded->potencia);
        $this->assertEquals('Negro', $vehiculoReloaded->color);
        $this->assertEquals('Diésel', $vehiculoReloaded->combustible);
        $this->assertEquals('N47D20C', $vehiculoReloaded->codmotor);
        $this->assertEquals('España', $vehiculoReloaded->procedencia_matricula);
        $this->assertEquals('2019-03-20', $vehiculoReloaded->fecha_primera_matriculacion);

        $vehiculo->delete();
    }

    /**
     * Test de reasignación a otro cliente
     */
    public function testReasignarACliente(): void
    {
        // Crear segundo cliente de prueba
        $cliente2 = new \FacturaScripts\Dinamic\Model\Cliente();
        if (!$cliente2->loadFromCode('TEST002')) {
            $cliente2->codcliente = 'TEST002';
            $cliente2->nombre = 'Cliente de Prueba 2';
            $cliente2->cifnif = '87654321B';
            $cliente2->save();
        }

        $vehiculo = new Vehiculo();
        $vehiculo->marca = 'Audi';
        $vehiculo->modelo = 'A4';
        $vehiculo->matricula = 'YZA7890';
        $vehiculo->codcliente = 'TEST001';
        $this->assertTrue($vehiculo->save());

        // Reasignar a otro cliente
        $this->assertTrue($vehiculo->reasignarACliente('TEST002'));
        $this->assertEquals('TEST002', $vehiculo->codcliente);

        // Verificar que se guardó
        $vehiculoReloaded = new Vehiculo();
        $this->assertTrue($vehiculoReloaded->loadFromCode($vehiculo->idmaquina));
        $this->assertEquals('TEST002', $vehiculoReloaded->codcliente);

        $vehiculo->delete();
        $cliente2->delete();
    }

    /**
     * Test de fabricante
     */
    public function testFabricante(): void
    {
        // Crear un fabricante de prueba
        $fabricante = new \FacturaScripts\Dinamic\Model\Fabricante();
        if (!$fabricante->loadFromCode('TEST')) {
            $fabricante->codfabricante = 'TEST';
            $fabricante->nombre = 'Fabricante Test';
            $fabricante->save();
        }

        $vehiculo = new Vehiculo();
        $vehiculo->marca = 'Test';
        $vehiculo->modelo = 'Model X';
        $vehiculo->matricula = 'BCD1234';
        $vehiculo->codcliente = 'TEST001';
        $vehiculo->codfabricante = 'TEST';
        $this->assertTrue($vehiculo->save());

        // Test: getFabricante()
        $fabResult = $vehiculo->getFabricante();
        $this->assertNotNull($fabResult);
        $this->assertEquals('TEST', $fabResult->codfabricante);
        $this->assertEquals('Fabricante Test', $fabResult->nombre);

        $vehiculo->delete();
        $fabricante->delete();
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }

    public static function tearDownAfterClass(): void
    {
        // Limpiar cliente de prueba
        $cliente = new \FacturaScripts\Dinamic\Model\Cliente();
        if ($cliente->loadFromCode('TEST001')) {
            $cliente->delete();
        }
    }
}
