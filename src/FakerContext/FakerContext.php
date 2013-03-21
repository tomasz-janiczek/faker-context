<?php
// TODO: add support for generating test data within sentences ie "[$c=company] is my favorite company"

namespace FakerContext;

use Behat\Behat\Context\BehatContext,
    Behat\Gherkin\Node\TableNode;

class FakerContext extends BehatContext
{
    const GENERATE_TEST_DATA_REGEX = '~\[([a-zA-Z0-9]+)=([a-zA-Z]+)(\(([0-9]+)\))?\]~';
    const GET_TEST_DATA_REGEX = '~\[([a-zA-Z0-9]+)\]~';
    private $generatedTestData;

    /**
     * @BeforeScenario
     */
    public function setUp($event)
    {
        $this->generatedTestData = array();
    }

    /**
     * @Transform /^([^"]*)$/
     */
    public function transformTestData($arg)
    {
        if ($arg instanceof TableNode) {
            return $this->transformTable($arg);
        } else {
            return $this->transformValue($arg);
        }
    }

    /**
     * @param $fakerProperty
     * @param null $fakerParameter
     * @return mixed
     */
    public function generateTestData($fakerProperty, $fakerParameter = null)
    {
        $fakerProperty = (string) $fakerProperty;

        if ($fakerParameter) {
            return $this->getFaker()->$fakerProperty($fakerParameter);
        } else {
            return $this->getFaker()->$fakerProperty;
        }
    }

    /**
     * @param TableNode $table
     * @return TableNode
     */
    public function transformTable(TableNode $table)
    {
        $rows = array();

        foreach ($table->getRows() as $row) {
            foreach ($row as $key => $value) {
                $row[$key] = $this->transformValue($value);
            }

            $rows[] = $row;
        }

        $tableNode = new TableNode();
        $tableNode->setRows($rows);
        return $tableNode;
    }

    /**
     * @param $string
     * @return mixed
     */
    public function transformValue($string)
    {
        if (preg_match(self::GENERATE_TEST_DATA_REGEX, $string, $matches)) {
            $key = $matches[1];
            $fakerProperty = $matches[2];
            $fakerParameter = $matches[4];

            $testData = $this->generateTestData($fakerProperty, $fakerParameter);

            $this->setTestData($key,$testData);

            return $testData;
        } else if (preg_match(self::GET_TEST_DATA_REGEX, $string, $matches)) {
            $position = $matches[1];
            $testData = $this->getTestData($position);

            return $testData;
        }

        return $string;
    }

    /**
     * @param $key
     * @param $value
     */
    protected function setTestData($key, $value)
    {
        $this->generatedTestData[$key] = $value;
    }

    /**
     * @param $position
     * @return mixed
     */
    protected function getTestData($position)
    {
        return $this->generatedTestData[$position];
    }

    protected function getFaker()
    {
        if (!$this->faker) {
            $this->faker = \Faker\Factory::create();
        }

        return $this->faker;
    }
}
