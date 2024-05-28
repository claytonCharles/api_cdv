<?php

namespace AppTest\Model;
use App\Entity\AutenticarEntity;
use App\Entity\CadastroUsuarioEntity;
use App\Model\AuthModel;
use AppTest\InMemoryContainer;
use Laminas\Authentication\Adapter\AdapterInterface;
use Laminas\Db\Adapter\Adapter;
use Laminas\Form\Annotation\AttributeBuilder;
use Laminas\Form\FormInterface;
use PHPUnit\Framework\TestCase;

class AuthModelTest extends TestCase
{
    /** @var array */
    protected $config;

    /** @var AdapterInterface */
    protected $adapter;

    /** @var AuthModel */
    protected $authModel;

    protected $attributeBuilder;


    protected function setUp(): void
    {
        $this->config = require(getcwd() . "/config/autoload/dev.local.php");
        $this->adapter = new Adapter($this->config["db"]);
        $this->authModel = new AuthModel($this->adapter);
        $this->attributeBuilder = new AttributeBuilder();
    }

    public function testeCadastroUsuarioValido(): void
    {   
        $cadastroEntity = new CadastroUsuarioEntity();
        $formulario = $this->attributeBuilder->createForm($cadastroEntity);
        $formulario->bind($cadastroEntity)->setData([
            "ds_nome" => "Clayton <strong>Charles</strong> Silva <?php echo Neres",
            "ds_email" => "testeUnitario@hotmail.com",
            "ds_senha" => "teste123"
        ]);
        $formularioValido = $formulario->isValid();
        $result = $this->authModel->cadastrarUsuario($formulario->getData(FormInterface::VALUES_NORMALIZED), $this->config["jwt"]);
        
        self::assertTrue($formularioValido);
        self::assertIsString($result);
        self::assertIsArray(explode(".", $result));
        self::assertEquals(3, count(explode(".", $result)));
    }

    public function testeAutenticarUsuarioInvalido(): void
    {
        $autenticarEntity = new AutenticarEntity();
        $formulario = $this->attributeBuilder->createForm($autenticarEntity);
        $formulario->bind($autenticarEntity)->setData([
            "ds_email" => "testeUnitario2@hotmail.com",
            "ds_senha" => "teste123"
        ]);
        $formularioValido = $formulario->isValid();
        $result = $this->authModel->autenticarUsuario($formulario->getData(FormInterface::VALUES_NORMALIZED), $this->config["jwt"]);

        self::assertTrue($formularioValido);
        self::assertEmpty($result);
    }
}