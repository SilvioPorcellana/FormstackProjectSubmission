<?php

/**
 * Class ApiCest
 *
 * This class tests API calls agains the "tasks" endpoint, checking if it works, if tasks can be retrieved,
 * added and deleted
 */

class DocumentApiCest
{
    /**
     * Creates a new document (POST)
     *
     * @param ApiTester $I
     */
    public function createDocumentViaAPI(\ApiTester $I)
    {
        /*
         * TODO
         */
        #$I->amHttpAuthenticated('service_user', '123456');

        $I->sendPOST('/document', ['name' => 'document via API', 'rows' => ['0' => [ 'key' => 'key_api', 'value' => 'value_api']]]);
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }


    /**
     * Checks if the task created in the step above is present
     *
     * @param ApiTester $I
     */
    public function checkDocumentViaAPI(\ApiTester $I)
    {
       $I->sendGET('/documents');
       $I->seeResponseCodeIs(200);
       $I->seeResponseIsJson();
       $I->seeResponseContainsJson([
           'name' => 'document via API',
       ]);
    }


    /**
     * Retrieves the list of tasks created by tests and deletes them, and then checks that no API tasks are present
     *
     * @param ApiTester $I
     */
    public function checkGetDocumentAndDeleteViaAPI(\ApiTester $I)
    {
       $I->sendGET('/documents');
       $I->seeResponseCodeIs(200);
       $I->seeResponseIsJson();
       $response = $I->grabResponse();
       $documents = json_decode($response);
       foreach ($documents as $document)
       {
           $I->sendDELETE('/document/' . $document->id);
           $I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK); // 200
       }

       $I->sendGET('/documents');
       $I->seeResponseCodeIs(200);
       $I->seeResponseIsJson();
       $I->dontSeeResponseContainsJson([
           'name' => 'document via API',
       ]);
    }

}