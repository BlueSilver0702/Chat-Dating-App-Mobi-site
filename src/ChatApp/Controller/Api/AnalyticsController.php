<?php

namespace ChatApp\Controller\Api;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class AnalyticsController implements ControllerProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function connect(Application $app)
    {
        $index = $app['controllers_factory'];

        $index->get('/promoters', 'ChatApp\Controller\Api\AnalyticsController::promotersAction');

        return $index;
    }

    /**
     * Returns promotion stats.
     *
     * @Route("/promoters")
     * @method("GET")
     */
    public function promotersAction(Application $app, Request $request)
    {
        $results = $app['user.repository']->getPromoterStats();
        if ($results) {
            $stats = array();
            foreach ($results as $result) {
                $promoCode = $result['promo_code'];
                $date = $result['date'];
                $stats[$promoCode][$date] = (int)$result['total'];
            }
        }
        return $app->json(array(
            'success' => true,
            'data' => array(
                'stats' => $stats,
            ),
        ));
    }
}
