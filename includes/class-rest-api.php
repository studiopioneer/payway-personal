<?php

if (!defined('ABSPATH')) {
    exit; // Защита от прямого доступа
}

/**
 * Класс для обработки REST API запросов платежного шлюза
 *
 * Отвечает за регистрацию и обработку всех REST API маршрутов,
 * связанных с функционалом платежного шлюза
 */
class PaywayRestApi
{
    /**
     * Массив конфигурации маршрутов REST API
     *
     * Каждый элемент массива содержит:
     * - route: путь маршрута
     * - methods: разрешенные HTTP методы
     * - controller: имя класса контроллера
     * - handler: имя метода-обработчика
     *
     * @var array
     */
    private array $routes = [
        [
            'route' => '/withdrawal',
            'methods' => ['POST', 'GET', 'DELETE'],
            'controller' => 'WithdrawalController'
        ],
        [
            'route' => '/unlock',
            'methods' => ['POST', 'GET', 'DELETE'],
            'controller' => 'UnlockController'
        ],
        [
            'route' => '/projects',
            'methods' => ['POST', 'GET', 'DELETE'],
            'controller' => 'ProjectsController'
        ],
        [
            'route' => '/stats',
            'methods' => ['GET'],
            'controller' => 'StatsController'
        ],
        [
            'route' => '/stats/get-by-month',
            'methods' => ['GET'],
            'controller' => 'StatsController',
            'handler' => 'get_stats_by_month'
        ],
        [
            'route' => '/stats/monthly-balance',
            'methods' => ['GET'],
            'controller' => 'StatsController',
            'handler' => 'calculate_monthly_balance'
        ],
        [
            'route' => '/stats/available-months',
            'methods' => ['GET'],
            'controller' => 'StatsController',
            'handler' => 'get_available_months'
        ],
        [
            'route' => '/user/balance',
            'methods' => ['GET'],
            'controller' => 'StatsController',
            'handler' => 'get_user_balance'
        ],
        [
            'route' => '/register',
            'methods' => ['POST'],
            'controller' => 'RegistrationController'
        ],
    ];

    /**
     * Инициализация класса
     *
     * Регистрирует необходимые хуки WordPress
     */
    public function __construct()
    {
        $this->init_hooks();
    }

    /**
     * Регистрация хуков WordPress
     *
     * Добавляет обработчик для инициализации REST API маршрутов
     */
    protected function init_hooks(): void
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Регистрация всех маршрутов REST API
     *
     * Подключает необходимые файлы контроллеров и регистрирует
     * все маршруты из конфигурации
     */
    public function register_routes(): void
    {
        require_once PAYWAY_PLUGIN_DIR . '/includes/controllers/class-base-controller.php';
        require_once PAYWAY_PLUGIN_DIR . '/includes/controllers/class-withdrawal-controller.php';
        require_once PAYWAY_PLUGIN_DIR . '/includes/controllers/class-unlock-controller.php';
        require_once PAYWAY_PLUGIN_DIR . '/includes/controllers/class-projects-controller.php';
        require_once PAYWAY_PLUGIN_DIR . '/includes/controllers/class-stats-controller.php';
        require_once PAYWAY_PLUGIN_DIR . '/includes/controllers/class-registration-controller.php';

        foreach ($this->routes as $route) {
            foreach ($route['methods'] as $method) {
                $this->register_route($route['route'], $method, $route['controller'], $route['handler'] ?? null);
            }
        }
    }

    /**
     * Регистрация отдельного маршрута REST API
     *
     * @param string $route_name Путь маршрута
     * @param string $method HTTP метод
     * @param string $controller Имя класса контроллера
     * @param string|null $handler Имя метода-обработчика (опционально)
     */
    private function register_route(string $route_name, string $method, string $controller, string $handler = null): void
    {
        if ($method === "DELETE") {
            $route_name .= '/(?P<id>\d+)';
        }

        // Настройка методов по умолчанию
        $method_to_handler = [
            "GET" => 'handle_get_request',
            "DELETE" => 'handle_delete_request',
            "POST" => 'handle_create_request'
        ];


        // Referral routes
        require_once STARTER_PLUGIN_DIR . 'includes/controllers/class-referral-controller.php';
        $referral = new ReferralController();

        register_rest_route( 'payway/v1', '/referrals/link', [
            'methods'  => 'GET',
            'callback' => [ $referral, 'handle_get_request' ],
            'permission_callback' => function() { return is_user_logged_in(); },
        ]);

        register_rest_route( 'payway/v1', '/referrals/list', [
            'methods'  => 'GET',
            'callback' => [ $referral, 'get_my_referrals' ],
            'permission_callback' => function() { return is_user_logged_in(); },
        ]);

        register_rest_route( 'payway/v1', '/referrals/all', [
            'methods'  => 'GET',
            'callback' => [ $referral, 'get_all_referrals' ],
            'permission_callback' => function() { return current_user_can( 'manage_options' ); },
        ]);
        register_rest_route('payway/v1', $route_name, [
            'methods' => $method,
            'callback' => function (WP_REST_Request $request) use ($controller, $method_to_handler, $method, $handler) {
                $resolved_handler = $handler ?? ($method_to_handler[$method] ?? 'handle_create_request');
                return $this->handle_request($controller, $resolved_handler, $request);
            },
            'permission_callback' => function (WP_REST_Request $request) use ($controller) {
                $instance = new $controller($request);
                return $instance->check_permissions();
            }
        ]);
    }

    /**
     * Обработчик запросов для маршрутов
     *
     * Создает экземпляр контроллера и вызывает соответствующий метод
     *
     * @param string $controller Имя класса контроллера
     * @param string $handler Имя метода-обработчика
     * @param WP_REST_Request $request Объект запроса WordPress
     * @return mixed Результат обработки запроса
     */
    private function handle_request(string $controller, string $handler, WP_REST_Request $request): mixed
    {
        $controller_instance = new $controller($request);
        return $controller_instance->$handler();
    }
}

new PaywayRestApi();