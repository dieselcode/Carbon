<?php

use \Carbon\Core\Protocol;

class ProtocolTest extends PHPUnit_Framework_TestCase
{

    public function testHexNumericComparisons()
    {
        $this->assertEquals(Protocol::TextFrame,    1);
        $this->assertEquals(Protocol::BinaryFrame,  0x02);
        $this->assertEquals(Protocol::CloseFrame,   dechex(8));
    }

}

?>