<?php declare(strict_types=1);
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 *
 */

/**
 * Test class for Sales Offer Controller
 */
class Sales_BoilerplateControllerTest extends TestCase
{
    protected $ctrl;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->ctrl = Sales_Controller_Boilerplate::getInstance();
    }

    public function testUpdate()
    {
        // lets just have one hanging around
        $this->ctrl->create($this->getBoilerplate());

        $createdBoiler = $this->ctrl->create($this->getBoilerplate());

        $createdBoiler->{Sales_Model_Boilerplate::FLD_NAME} = Tinebase_Record_Abstract::generateUID();
        $updatedBoilder = $this->ctrl->update($createdBoiler);
        $this->assertSame($createdBoiler->{Sales_Model_Boilerplate::FLD_NAME},
            $updatedBoilder->{Sales_Model_Boilerplate::FLD_NAME});

        $createdBoiler->{Sales_Model_Boilerplate::FLD_FROM} = Tinebase_DateTime::now();
        $updatedBoilder = $this->ctrl->update($createdBoiler);
        $this->assertSame((string)$createdBoiler->{Sales_Model_Boilerplate::FLD_FROM},
            (string)$updatedBoilder->{Sales_Model_Boilerplate::FLD_FROM});

        $createdBoiler->{Sales_Model_Boilerplate::FLD_FROM} = null;
        $updatedBoilder = $this->ctrl->update($createdBoiler);
        $this->assertSame($createdBoiler->{Sales_Model_Boilerplate::FLD_FROM},
            $updatedBoilder->{Sales_Model_Boilerplate::FLD_FROM});

        $updatedBoilder->setId(null);
        $updatedBoilder->{Sales_Model_Boilerplate::FLD_FROM} = ($date = Tinebase_DateTime::now())->setTime(0, 0, 0, 0);
        $updatedBoilder = $this->ctrl->create($updatedBoilder);
        $this->assertSame((string)$updatedBoilder->{Sales_Model_Boilerplate::FLD_FROM}, (string)$date);

        $updatedBoilder->{Sales_Model_Boilerplate::FLD_UNTIL} = $date->getClone()->addDay(1);
        $updatedBoilder = $this->ctrl->update($updatedBoilder);
        $this->assertSame((string)$updatedBoilder->{Sales_Model_Boilerplate::FLD_UNTIL},
            (string)$date->getClone()->addDay(1));
    }

    public function testConstraintsWorking1()
    {
        $boiler = $this->getBoilerplate();
        $boiler->{Sales_Model_Boilerplate::FLD_FROM} = ($date = Tinebase_DateTime::now())->setTime(0, 0, 0, 0);
        $boiler->{Sales_Model_Boilerplate::FLD_UNTIL} = $date->getClone()->addDay(1);

        $this->ctrl->create($boiler);

        $boiler->setId(null);
        $boiler->{Sales_Model_Boilerplate::FLD_FROM} = null;
        $boiler->{Sales_Model_Boilerplate::FLD_UNTIL} = null;
        $this->ctrl->create($boiler);
    }

    public function testConstraintsWorking2()
    {
        $boiler = $this->getBoilerplate();
        $boiler->{Sales_Model_Boilerplate::FLD_FROM} = ($date = Tinebase_DateTime::now())->setTime(0, 0, 0, 0);

        $this->ctrl->create($boiler);

        $boiler->setId(null);
        $boiler->{Sales_Model_Boilerplate::FLD_FROM} = null;
        $boiler->{Sales_Model_Boilerplate::FLD_UNTIL} = null;
        $this->ctrl->create($boiler);
    }

    public function testConstraintsWorking3()
    {
        $boiler = $this->getBoilerplate();
        $boiler->{Sales_Model_Boilerplate::FLD_UNTIL} = Tinebase_DateTime::now();

        $this->ctrl->create($boiler);

        $boiler->setId(null);
        $boiler->{Sales_Model_Boilerplate::FLD_FROM} = null;
        $boiler->{Sales_Model_Boilerplate::FLD_UNTIL} = null;
        $this->ctrl->create($boiler);
    }

    public function testConstraintsWorking4()
    {
        $boiler = $this->getBoilerplate();
        $boiler->{Sales_Model_Boilerplate::FLD_UNTIL} = Tinebase_DateTime::now();

        $this->ctrl->create($boiler);

        $boiler->setId(null);
        $boiler->{Sales_Model_Boilerplate::FLD_FROM} = $boiler->{Sales_Model_Boilerplate::FLD_UNTIL}->addDay(1);
        $boiler->{Sales_Model_Boilerplate::FLD_UNTIL} = null;
        $this->ctrl->create($boiler);
    }

    public function testConstraintsWorking5()
    {
        $boiler = $this->ctrl->create($this->getBoilerplate());

        $boiler->setId(null);
        $boiler->{Sales_Model_Boilerplate::FLD_FROM} = ($date = Tinebase_DateTime::now())->setTime(0, 0, 0, 0);
        $boiler->{Sales_Model_Boilerplate::FLD_UNTIL} = $date->getClone()->addDay(1);
        $boiler = $this->ctrl->create($boiler);

        $boiler->setId(null);
        $boiler->{Sales_Model_Boilerplate::FLD_FROM}->addDay(2);
        $boiler->{Sales_Model_Boilerplate::FLD_UNTIL}->addDay(2);
        $this->ctrl->create($boiler);
    }

    public function testConstraintsFail1()
    {
        $created = $this->ctrl->create($this->getBoilerplate());
        $created->setId(null);

        $this->expectException(Tinebase_Exception_Record_Validation::class);
        $this->expectExceptionMessage('Name needs to be unique.');
        $this->ctrl->create($created);
    }

    public function testConstraintsFail2()
    {
        $created = $this->ctrl->create($this->getBoilerplate());
        $created->setId(null);
        $created->{Sales_Model_Boilerplate::FLD_FROM} = ($date = Tinebase_DateTime::now())->setTime(0, 0, 0, 0);
        $created->{Sales_Model_Boilerplate::FLD_UNTIL} = $date->getClone()->addDay(1);
        $created = $this->ctrl->create($created);
        $created->setId(null);

        $dates = '"' . $created->{Sales_Model_Boilerplate::FLD_FROM} . ' - ' .
            $created->{Sales_Model_Boilerplate::FLD_UNTIL} . '"';
        $this->expectException(Tinebase_Exception_Record_Validation::class);
        $this->expectExceptionMessage('Dates ' . $dates . ' overlap with existing records dates ' . $dates);
        $this->ctrl->create($created);
    }

    public function testConstraintsFail3()
    {
        $created = $this->ctrl->create($this->getBoilerplate());
        $created->setId(null);
        $created->{Sales_Model_Boilerplate::FLD_FROM} = ($date = Tinebase_DateTime::now())->setTime(0, 0, 0, 0);
        $created->{Sales_Model_Boilerplate::FLD_UNTIL} = $date->getClone()->addDay(1);
        $created = $this->ctrl->create($created);
        $created->setId(null);
        $created->{Sales_Model_Boilerplate::FLD_FROM} = $created->{Sales_Model_Boilerplate::FLD_UNTIL};
        $created->{Sales_Model_Boilerplate::FLD_UNTIL} = null;

        $this->expectException(Tinebase_Exception_Record_Validation::class);
        $this->ctrl->create($created);
    }

    public function testConstraintsFail4()
    {
        $created = $this->ctrl->create($this->getBoilerplate());
        $created->setId(null);
        $created->{Sales_Model_Boilerplate::FLD_UNTIL} = Tinebase_DateTime::now();
        $created = $this->ctrl->create($created);
        $created->setId(null);
        $created->{Sales_Model_Boilerplate::FLD_FROM} = $created->{Sales_Model_Boilerplate::FLD_UNTIL};
        $created->{Sales_Model_Boilerplate::FLD_UNTIL} = null;

        $this->expectException(Tinebase_Exception_Record_Validation::class);
        $this->ctrl->create($created);
    }

    public static function getBoilerplate($data = [])
    {
        return new Sales_Model_Boilerplate(array_merge([
            Sales_Model_Boilerplate::FLD_NAME           => Tinebase_Record_Abstract::generateUID(),
            Sales_Model_Boilerplate::FLD_MODEL          => Sales_Model_Document_Offer::class,
            Sales_Model_Boilerplate::FLD_BOILERPLATE    => 'lorem boiler ipsum plate',
            Sales_Model_Boilerplate::FLD_LANGUAGE       => 'en',
        ], $data));
    }
}
