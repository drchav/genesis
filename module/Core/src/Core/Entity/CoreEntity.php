<?php
namespace Core\Entity;

use Doctrine\ORM\Mapping as ORM;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\Factory as InputFactory;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;
use Zend\Stdlib\Hydrator\ClassMethods;

abstract class CoreEntity implements InputFilterAwareInterface
{
	
    /**
     * @ORM\Column(type="datetime")
     * @var datetime
     */
    protected $createdStamp;

    public function __construct()
    {
        $this->$createdStamp = new DateTime();
    }
    
    /**
     * Filtros
     * 
     * @var InputFilter
     */
    protected $inputFilter = null;

    /**
     * Filters and validators
     * @var array
     */
    protected $filters = array();

    /**
     * Valida e atribui os valores dos campos da entidade
     *
     * @param string $key   O nome do campo
     * @param string $value O valor do campo
     * @return void
     */
    public function __set($key, $value)
    {
        $this->$key = $this->valid($key, $value);
    }

    /**
     * Retorna os dados da entidade
     *
     * @param string $key O nome do campo
     * @return mixed 
     */
    public function __get($key)
    {
        return $this->$key;
    }

    /**
     * Atribui os valores para os atributos da entidade de acordo com um array de dados
     *
     * @param array $data Os dados a serem salvos
     * @return void
     */
    public function setData($data)
    {
        foreach ($data as $key => $value) {
            $this->__set($key, $value);
        }
    }

    /**
     * Retorna um array com todos os dados da entidade
     *
     * @return array
     */
    public function getData()
    {
        $data = get_object_vars($this);
        unset($data['inputFilter']);

        $filtered = array_filter($data);
        foreach ($data as $key => $value) {
            if ($value === 'Null') {
                $filtered[$key] = '';
            }

            if ($value === 'Zero') {
                $filtered[$key] = 0;
            }

        }
        return $filtered;
    }

    /**
     * Obrigatório para uso pelo TableGateway
     *
     * @param array $data Os dados da entidade
     * @return void
     */
    public function exchangeArray($data)
    {
        $this->setData($data);
    }

    /**
     * Obrigatório para uso pelo TableGateway
     *
     * @param array $data
     * @return void
     */
    public function getArrayCopy()
    {
        return $this->getData();
    }

    /**
     * Não é possível atribuir um inputFilter além do que está escrito na entidade
     *
     * @param InputFilterInterface $inputFilter
     * @return void
     */
    public function setInputFilter(InputFilterInterface $inputFilter)
    {
        throw new EntityException("Not used");
        return $inputFilter;
    }

    /**
     * Configura os filtros dos campos da entidade
     *
     * @return Zend\InputFilter\InputFilter
     */
    public function getInputFilter()
    {
        if (!$this->inputFilter) {
            $inputFilter = new InputFilter();
            $factory = new InputFactory();

            foreach ($this->filters as $f) {
                $inputFilter->add(
                    $factory->createInput($f)
                );
            }
            $this->inputFilter = $inputFilter;
        }

        return $this->inputFilter;
    }


    /**
     * Verifica se o campo está válido e faz o filtro
     *
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    protected function valid($key, $value)
    {
        if (! $this->getInputFilter()) {
            return $value;
        }

        try {
            $filter = $this->getInputFilter()->get($key);
        } catch (InvalidArgumentException $e) {
            //não existe filtro para esse campo
            return $value;
        }

        $filter->setValue($value);

        if (empty($value) && $filter->allowEmpty()) {
            return $filter->getValue($key);
        }

        if (! $filter->isValid()) {
            throw new EntityException("Input inválido: $key = $value");
        }

        return $filter->getValue($key);
    }
    

    /**
     * Retorna os dados em forma de array. Usado pelo TableGateway
     *
     * @return array
     */
    public function toArray()
    {
        $data = $this->getData();
        
        foreach ($data as $key => $value) {
            if ($value instanceof \DateTime) {
                $data[$key] = $value->format('d/m/Y H:i:s');
            }
        }
        return $data;
    }

}