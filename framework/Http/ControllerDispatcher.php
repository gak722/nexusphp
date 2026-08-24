<?php
declare(strict_types=1);

namespace Nexus\Http;

use Nexus\Foundation\Application;

/**
 * Controller Method Dispatcher with DI & Parameter Resolution
 */
class ControllerDispatcher
{
    public function __construct(protected Application $app) {}

    public function dispatch(object $controller, string $method, array $routeParams): Response
    {
        $reflector = new \ReflectionMethod($controller, $method);
        $args = [];

        foreach ($reflector->getParameters() as $param) {
            $name = $param->getName();
            $type = $param->getType();

            if (array_key_exists($name, $routeParams)) {
                $val = $routeParams[$name];
                if ($type instanceof \ReflectionNamedType && $type->isBuiltin()) {
                    $typeName = $type->getName();
                    if ($typeName === 'int') {
                        $val = (int) $val;
                    } elseif ($typeName === 'float') {
                        $val = (float) $val;
                    } elseif ($typeName === 'bool') {
                        $val = filter_var($val, FILTER_VALIDATE_BOOLEAN);
                    }
                }
                $args[] = $val;
                continue;
            }

            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                $className = $type->getName();
                if (is_a($className, \Nexus\Validation\FormRequest::class, true)) {
                    $request = $this->app->has(Request::class) ? $this->app->make(Request::class) : Request::createFromGlobals();
                    $formReq = $className::createFromRequest($request);
                    if ($formReq instanceof \Nexus\Validation\FormRequest) {
                        $formReq->validateResolved();
                    }
                    $args[] = $formReq;
                    continue;
                }

                if (is_a($className, \Nexus\Database\Model::class, true)) {
                    $request = $this->app->make(Request::class);
                    $args[] = $className::validateAndBind($request);
                    continue;
                }

                if ($this->app->has($className)) {
                    $args[] = $this->app->make($className);
                    continue;
                }

                if (class_exists($className)) {
                    $request = $this->app->make(Request::class);
                    $args[] = $request->validateAndBind($className);
                    continue;
                }

                $args[] = $this->app->make($className);
                continue;
            }

            if ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } else {
                $args[] = null;
            }
        }

        $result = $reflector->invokeArgs($controller, $args);

        if ($result instanceof Response) {
            return $result;
        }

        if (is_array($result) || $result instanceof \JsonSerializable) {
            return new JsonResponse($result);
        }

        return new Response((string) $result);
    }
}
