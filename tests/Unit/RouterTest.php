<?php

/**
 * Example Unit Test - Router Test
 */

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use NeoCore\System\Core\Router;
use NeoCore\System\Core\Request;
use NeoCore\System\Core\Response;

class RouterTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        $this->router = new Router();
    }

    public function testCanAddGetRoute(): void
    {
        $this->router->get('/test', 'TestController@index');
        
        $route = $this->router->match('GET', '/test');
        
        $this->assertNotNull($route);
        $this->assertEquals('TestController@index', $route['handler']);
    }

    public function testCanAddPostRoute(): void
    {
        $this->router->post('/users', 'UserController@store');
        
        $route = $this->router->match('POST', '/users');
        
        $this->assertNotNull($route);
        $this->assertEquals('UserController@store', $route['handler']);
    }

    public function testRouteWithParameters(): void
    {
        $this->router->get('/users/{id}', 'UserController@show');
        
        $route = $this->router->match('GET', '/users/123');
        
        $this->assertNotNull($route);
        $this->assertEquals('123', $route['params']['id']);
    }

    public function testRouteNotFound(): void
    {
        $route = $this->router->match('GET', '/nonexistent');
        
        $this->assertNull($route);
    }

    public function testRouteWithMultipleParameters(): void
    {
        $this->router->get('/posts/{id}/comments/{commentId}', 'CommentController@show');
        
        $route = $this->router->match('GET', '/posts/5/comments/10');
        
        $this->assertNotNull($route);
        $this->assertEquals('5', $route['params']['id']);
        $this->assertEquals('10', $route['params']['commentId']);
    }
}
