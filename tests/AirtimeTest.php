<?php

class AirtimeTest extends TestCase
{
    /** @test */
    public function testGetAirtimeProviders()
    {
        $response = $this->call('GET','/v1/airtime/providers');

        $response->isSuccessful();
        $this->seeJsonStructure([
            'message','data', "status"
        ]);
    }

}
