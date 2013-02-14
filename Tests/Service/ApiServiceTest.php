<?php
/**
 * Class definition for Malwarebytes\ZendeskBundle\Tests\Service\ApiServiceTest
 * @author relwell
 */
namespace Malwarebytes\ZendeskBundle\Tests\Service;

require_once( __DIR__. '/../../Service/ApiService.php' );

use MalwareBytes\ZendeskBundle\Service\ApiService;
use \zendesk;
use \ReflectionProperty;
use \ReflectionMethod;
/**
 * Tests for MalwareBytes\ZendeskBundle\Service\ApiService
 * @author relwell
 */
class ApiServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PHPUnit_Framework_MockObject_MockBuilder
     */
    protected $apiService;
    
    /**
     * @var \zendesk
     */
    protected $zendesk;
    
    /**
     * Keeps track of subdomain, since zendesk API doesn't
     * @var string
     */
    protected $subDomain;
    
    /**
     * Api key for zendesk 
     * @var string
     */
    protected $apiKey;
    
    /**
     * User ID interacting with zendesk
     * @var string
     */
    protected $user;
    
    public function setUp()
    {
        $this->apiService = $this->getMockBuilder( '\MalwareBytes\ZendeskBundle\Service\ApiService' )
                                 ->disableOriginalConstructor();
        
        $this->apiKey    = 'apiKey';
        $this->subDomain = 'subdomain';
        $this->user      = 'user';
        
        $this->zendesk = $this->getMockBuilder( '\zendesk' )
                              ->setConstructorArgs( array( $this->apiKey, $this->subDomain, $this->user ) )
                              ->setMethods( array( 'call' ) )
                              ->getMock();
    }
    
    /**
     * @covers MalwareBytes\ZendeskBundle\Service\ApiService::_get
     */
    public function test_get()
    {
        $service = $this->apiService->setMethods( null )->getMock();
        $path = 'path';
        $responseArray = array( 'foo' );
        $this->zendesk
            ->expects( $this->at( 0 ) )
            ->method ( 'call' )
            ->with   ( $path, '', 'GET' )
            ->will   ( $this->returnValue( $responseArray ) )
        ;
        $_get = new ReflectionMethod( 'Malwarebytes\ZendeskBundle\Service\ApiService', '_get' );
        $_get->setAccessible( true );
        $this->assertEquals(
                $responseArray,
                $_get->invoke( $service->setZendeskApi( $this->zendesk ), $path )
        );
    }
    
    /**
     * @covers MalwareBytes\ZendeskBundle\Service\ApiService::createUser
     */
    public function testCreateUser()
    {
        $service = $this->apiService->setMethods( null )->getMock();
        
        $name = 'Name';
        $email = 'email@foo.com';
        
        $dataArray = array(
                'name' => $name,
                'email' => $email,
                'verified' => true
                );
        
        $responseArray = array( 'mockresponse' );
        
        $this->zendesk
            ->expects( $this->at( 0 ) )
            ->method ( 'call' )
            ->with   ( '/users', json_encode( $dataArray ), 'POST' )
            ->will   ( $this->returnValue( $responseArray ) )
        ;
        $this->assertEquals(
                $responseArray,
                $service->setZendeskApi( $this->zendesk )
                        ->createUser   ( $name, $email )
        );
        $dataArray['verified'] = false;
        $this->zendesk
            ->expects( $this->at( 0 ) )
            ->method ( 'call' )
            ->with   ( '/users', json_encode( $dataArray ), 'POST' )
            ->will   ( $this->returnValue( $responseArray ) )
        ;
        $this->assertEquals(
                $responseArray,
                $service->setZendeskApi( $this->zendesk )
                        ->createUser   ( $name, $email, false )
        );
        
    }
    
    /**
     * @covers MalwareBytes\ZendeskBundle\Service\ApiService::createTicket
     */
    public function testCreateTicket()
    {
        $service = $this->apiService->setMethods( null )->getMock();
        
        $subject = 'My Printer Is On Fire';
        $comment = array(
                'body' => 'One of my idiot friends printed out that speaking printer joke so I set it on fire.'
                );
        $dataArray = array(
                'subject' => $subject,
                'comment' => $comment
                );
        $responseArray = array( 'mockresponse' );
        $service->setZendeskApi( $this->zendesk );
        try {
            $service->createTicket( $dataArray );
        } catch ( \UnexpectedValueException $e ) { }
        $dataArray['requester_id'] = 123;
        $this->zendesk
            ->expects( $this->once() )
            ->method ( 'call' )
            ->with   ( '/tickets', json_encode( $dataArray ), 'POST' )
            ->will   ( $this->returnValue( $responseArray ) )
        ; 
        $this->assertEquals(
                $responseArray,
                $service->createTicket( $dataArray )
        );
    }
    
    /**
     * @covers MalwareBytes\ZendeskBundle\Service\ApiService::getTicketsRequestedByUser
     */
    public function testGetTicketsRequestedByUser()
    {
        $service = $this->apiService->setMethods( array( '_get' ) )->getMock();
        $userId = 'userid';
        $response = array( 'mock' );
        $service
            ->expects( $this->at( 0 ) )
            ->method ( '_get' )
            ->with   ( "/users/{$userId}/tickets/requested" )
            ->will   ( $this->returnValue( $response ) )
        ;
        $this->assertEquals(
                $response,
                $service->getTicketsRequestedByUser( $userId )
        );
    }
    
    /**
     * @covers MalwareBytes\ZendeskBundle\Service\ApiService::userHasRequestedTickets 
     */
    public function testUserHasRequestedTicketsNoTickets()
    {
        $service = $this->apiService->setMethods( array( 'getTicketsRequestedByUser' ) )->getMock();
        $userId = 'userid';
        $service
            ->expects( $this->at( 0 ) )
            ->method ( 'getTicketsRequestedByUser' )
            ->with   ( $userId )
            ->will   ( $this->returnValue( array() ) )
        ;
        $this->assertFalse(
                $service->userHasRequestedTickets( $userId )
        );
    }
    
    /**
     * @covers MalwareBytes\ZendeskBundle\Service\ApiService::userHasRequestedTickets 
     */
    public function testUserHasRequestedTicketsWithTickets()
    {
        $service = $this->apiService->setMethods( array( 'getTicketsRequestedByUser' ) )->getMock();
        $userId = 'userid';
        $service
            ->expects( $this->at( 0 ) )
            ->method ( 'getTicketsRequestedByUser' )
            ->with   ( $userId )
            ->will   ( $this->returnValue( array( 'tickets' => array( array( 'here is one' ) ) ) ) )
        ;
        $this->assertTrue(
                $service->userHasRequestedTickets( $userId )
        );
    }
    
    /**
     * @covers MalwareBytes\ZendeskBundle\Service\ApiService::getTicketsCCedToUser
     */
    public function testGetTicketsCCedToUser()
    {
        $service = $this->apiService->setMethods( array( '_get' ) )->getMock();
        $userId = 'userid';
        $response = array( 'mock' );
        $service
            ->expects( $this->at( 0 ) )
            ->method ( '_get' )
            ->with   ( "/users/{$userId}/tickets/ccd" )
            ->will   ( $this->returnValue( $response ) )
        ;
        $this->assertEquals(
                $response,
                $service->getTicketsCCedToUser( $userId )
        );
    }
    
    /**
     * @covers MalwareBytes\ZendeskBundle\Service\ApiService::userHasCCs 
     */
    public function testUserHasCCsNoTickets()
    {
        $service = $this->apiService->setMethods( array( 'getTicketsCCedToUser' ) )->getMock();
        $userId = 'userid';
        $service
            ->expects( $this->at( 0 ) )
            ->method ( 'getTicketsCCedToUser' )
            ->with   ( $userId )
            ->will   ( $this->returnValue( array() ) )
        ;
        $this->assertFalse(
                $service->userHasCCs( $userId )
        );
    }
    
    /**
     * @covers MalwareBytes\ZendeskBundle\Service\ApiService::userHasCCs 
     */
    public function testUserHasCCsWithTickets()
    {
        $service = $this->apiService->setMethods( array( 'getTicketsCCedToUser' ) )->getMock();
        $userId = 'userid';
        $service
            ->expects( $this->at( 0 ) )
            ->method ( 'getTicketsCCedToUser' )
            ->with   ( $userId )
            ->will   ( $this->returnValue( array( 'tickets' => array( array( 'here is one' ) ) ) ) )
        ;
        $this->assertTrue(
                $service->userHasCCs( $userId )
        );
    }
    
    /**
     * @covers MalwareBytes\ZendeskBundle\Service\ApiService::setZendeskApi
     */
    public function testSetZendeskApi()
    {
        $service = $this->apiService->setMethods( null )->getMock();
        $this->assertEquals(
                $service,
                $service->setZendeskApi( $this->zendesk )
        );
        $this->assertAttributeEquals(
                $this->apiKey,
                'apiKey',
                $service
        );
    }
    
    /**
     * @covers MalwareBytes\ZendeskBundle\Service\ApiService::updateTicket
     */
    public function testUpdateTicket()
    {
        $service = $this->apiService->setMethods( null )->getMock();
        $ticketId = 123;
        $data = array( 'foo' => 'bar' );
        $preparedData = array( 'ticket' => $data );
        $response = array( 'response' );
        $this->zendesk
            ->expects( $this->once() )
            ->method ( 'call' )
            ->with   ( "/tickets/{$ticketId}", json_encode( $preparedData ), 'PUT' )
            ->will   ( $this->returnValue( $response ) )
        ;
        $this->assertEquals(
                $response,
                $service->setZendeskApi( $this->zendesk )->updateTicket( $ticketId, $data )
        );
    }
    
    /**
     * @covers MalwareBytes\ZendeskBundle\Service\ApiService::addCommentToTicket
     */
    public function testAddCommentToTicket()
    {
        $service = $this->apiService->setMethods( array( 'updateTicket' ) )->getMock();
        $ticketId = 123;
        $comment = 'This is my comment';
        $data = array(
                'comment' => array(
                        'public' => false,
                        'body'   => $comment
                        )
                );
        $response = array( 'response' );
        $service
            ->expects( $this->once() )
            ->method ( 'updateTicket' )
            ->with   ( $ticketId, $data )
            ->will   ( $this->returnValue( $response ) )
        ;
        $this->assertEquals(
                $response,
                $service->addCommentToTicket( $ticketId, $comment, false )
        );
    }
    
    /**
     * @covers MalwareBytes\ZendeskBundle\Service\ApiService::assignTicketToUser
     */
    public function testAssignTicketToUser()
    {
        $service = $this->apiService->setMethods( array( 'updateTicket' ) )->getMock();
        $response = array( 'response' );
        $userId = 123;
        $ticketId = 234;
        $service
            ->expects( $this->once() )
            ->method ( 'updateTicket' )
            ->with   ( $ticketId, array( 'assignee_id' => $userId ) )
            ->will   ( $this->returnValue( $response ) )
        ;
        $this->assertEquals(
                $response,
                $service->assignTicketToUser( $ticketId, $userId )
        );
    }
    
    /**
     * @covers MalwareBytes\ZendeskBundle\Service\ApiService::assignTicketToGroup
     */
    public function testAssignTicketToGroup()
    {
        $service = $this->apiService->setMethods( array( 'updateTicket' ) )->getMock();
        $response = array( 'response' );
        $groupId = 123;
        $ticketId = 234;
        $service
            ->expects( $this->once() )
            ->method ( 'updateTicket' )
            ->with   ( $ticketId, array( 'group_id' => $groupId ) )
            ->will   ( $this->returnValue( $response ) )
        ;
        $this->assertEquals(
                $response,
                $service->assignTicketToGroup( $ticketId, $groupId )
        );
    }
    
    /**
     * @covers MalwareBytes\ZendeskBundle\Service\ApiService::_search
     */
    public function test_search()
    {
        $apiService = $this->apiService->setMethods( array( '_get' ) )->getMock();
        $_search = new ReflectionMethod( 'MalwareBytes\ZendeskBundle\Service\ApiService', '_search' );
        $_search->setAccessible( true );
        
        $queryString = 'foo:bar';
        $path = "/search?" . http_build_query( array( 'query' => $queryString ) );
        $response = array( 'my response' );
        
        $apiService
            ->expects( $this->once() )
            ->method ( '_get' )
            ->with   ( $path )
            ->will   ( $this->returnValue( $response ) )
        ;
        $this->assertEquals(
                $response,
                $_search->invoke( $apiService, $queryString )
        );
    }
    
    /**
     * @covers MalwareBytes\ZendeskBundle\Service\ApiService::getTicket
     */
    public function testGetTicket()
    {
        $apiService = $this->apiService->setMethods( array( '_get' ) )->getMock();
        $response = array( 'response' );
        $ticketId = 123;
        $apiService
            ->expects( $this->once() )
            ->method ( '_get' )
            ->with   ( "/tickets/{$ticketId}" )
            ->will   ( $this->returnValue( $response ) )
        ;
        $this->assertEquals(
                $response,
                $apiService->getTicket( $ticketId )
        );
    }
    
    /**
     * @covers MalwareBytes\ZendeskBundle\Service\ApiService::getTicketsAssignedToUser
     */
    public function testGetTicketsAssignedToUser()
    {
        $apiService = $this->apiService->setMethods( array( '_search' ) )->getMock();
        $response = array( 'my response' );
        $user = 'foo@bar.com';
        $apiService
            ->expects( $this->once() )
            ->method ( '_search' )
            ->with   ( sprintf( 'type:ticket assignee:%s', $user ) )
            ->will   ( $this->returnValue( $response ) )
        ;
        $this->assertEquals(
                $response,
                $apiService->getTicketsAssignedToUser( $user )
        );
    }
    
    /**
     * @covers MalwareBytes\ZendeskBundle\Service\ApiService::getTicketsForGroup
     */
    public function testGetTicketsForGroup()
    {
        $apiService = $this->apiService->setMethods( array( '_search' ) )->getMock();
        $response = array( 'my response' );
        $group = 'quality assurance';
        $apiService
            ->expects( $this->once() )
            ->method ( '_search' )
            ->with   ( sprintf( 'type:ticket group:%s', $group ) )
            ->will   ( $this->returnValue( $response ) )
        ;
        $this->assertEquals(
                $response,
                $apiService->getTicketsForGroup( $group )
        );
    }
    
    /**
     * @covers MalwareBytes\ZendeskBundle\Service\ApiService::setTicketCollaborators
     */
    public function testSetTicketCollaborators()
    {
        $apiService = $this->apiService->setMethods( array( 'updateTicket' ) )->getMock();
        $ticketId = 123;
        $collaboratorIds = array( 456, 789 );
        $response = array( 'my response' );
        $apiService
            ->expects( $this->once() )
            ->method ( 'updateTicket' )
            ->with   ( $ticketId, array( 'collaborator_ids' => $collaboratorIds ) )
            ->will   ( $this->returnValue( $response ) )
        ;
        $this->assertEquals(
                $response,
                $apiService->setTicketCollaborators( $ticketId, $collaboratorIds )
        );
    }
    
    /**
     * @covers MalwareBytes\ZendeskBundle\Service\ApiService::addCollaboratorToTicket
     */
    public function testAddCollaboratorToTicket()
    {
        $apiService = $this->apiService->setMethods( array( 'getTicket', 'setTicketCollaborators' ) )->getMock();
        $ticketId = 123;
        $collaboratorIds = array( 456, 789 );
        $ticketResponse = array( 'ticket' => array( 'id' => $ticketId, 'collaborator_ids' => $collaboratorIds ) );
        $collaboratorId = 987;
        $response = array( 'my response' );
        $apiService
            ->expects( $this->once() )
            ->method ( 'getTicket' )
            ->with   ( $ticketId )
            ->will   ( $this->returnValue( $ticketResponse ) )
        ;
        $apiService
            ->expects( $this->once() )
            ->method ( 'setTicketCollaborators' )
            ->with   ( $ticketId, array_merge( $collaboratorIds, array( $collaboratorId ) ) )
            ->will   ( $this->returnValue( $response ) )
        ;
        $this->assertEquals(
                $response,
                $apiService->addCollaboratorToTicket( $ticketId, $collaboratorId )
        );
    }
    
    /**
     * @covers MalwareBytes\ZendeskBundle\Service\ApiService::getTicketUntouchedSinceTime
     */
    public function testGetTicketsUntouchedSinceTime()
    {
        $apiService = $this->apiService->setMethods( array( '_search' ) )->getMock();
        $response = array( 'my response' );
        $timestamp = time() - 43200;
        $apiService
            ->expects( $this->any() )
            ->method ( '_search' )
            ->with   ( sprintf( 'type:ticket updated_at<%s', gmdate( 'Y-m-d\TH:i:s\Z', $timestamp ) ) )
            ->will   ( $this->returnValue( $response ) )
        ;
        $this->assertEquals(
                $response,
                $apiService->getTicketsUntouchedSinceTime( $timestamp )
        );
    }
}
