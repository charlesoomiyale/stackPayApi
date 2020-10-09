<?php

class DataTest extends TestCase
{
    /** @test */
    public function testGetDataProviders()
    {
        $response = $this->call('GET','/v1/data/providers');

        $response->isSuccessful();
        $this->seeJsonStructure([
            'message','data', "status"
        ]);
    }

    /** @test */
    public function testGetDataProviderSmile()
    {
        $response = $this->call('GET','/v1/data/provider/smile');

        $response->isSuccessful();
        $this->seeJsonStructure([
            'message','data', "status"
        ]);
    }


    /** @test */
    public function testGetDataProviderMtn()
    {
        $response = $this->call('GET','/v1/data/provider/mtn');

        $response->isSuccessful();
        $this->seeJsonStructure([
            'message','data', "status"
        ]);
    }


    /** @test */
    public function testGetDataProviderAirtel()
    {
        $response = $this->call('GET','/v1/data/provider/airtel');

        $response->isSuccessful();
        $this->seeJsonStructure([
            'message','data', "status"
        ]);
    }


    /** @test */
    public function testGetDataProviderGlo()
    {
        $response = $this->call('GET','/v1/data/provider/glo');

        $response->isSuccessful();
        $this->seeJsonStructure([
            'message','data', "status"
        ]);
    }

}
