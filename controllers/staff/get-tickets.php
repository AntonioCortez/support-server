<?php
use Respect\Validation\Validator as DataValidator;

/**
 * @api {post} /staff/get-tickets Get tickets
 * @apiVersion 4.8.0
 *
 * @apiName Get tickets
 *
 * @apiGroup Staff
 *
 * @apiDescription This path retrieves the tickets assigned to the current staff member.
 *
 * @apiPermission staff1
 *
 * @apiParam {Number} page The page number.
 * @apiParam {bool} closed Include closed tickets in the response.
 *
 * @apiUse NO_PERMISSION
 * @apiUse INVALID_PAGE
 *
 * @apiSuccess {Object} data Information about a tickets and quantity of pages.
 * @apiSuccess {[Ticket](#api-Data_Structures-ObjectTicket)[]} data.tickets Array of tickets assigned to the staff of the current page.
 * @apiSuccess {Number} data.page Number of current page.
 * @apiSuccess {Number} data.pages Quantity of pages.
 *
 */

class GetTicketStaffController extends Controller {
    const PATH = '/get-tickets';
    const METHOD = 'POST';

    public function validations() {
        return [
            'permission' => 'staff_1',
            'requestData' => [
                'page' => [
                    'validation' => DataValidator::numeric(),
                    'error' => ERRORS::INVALID_PAGE
                ]
            ]
        ];
    }

    public function handler() {
        $user = Controller::getLoggedUser();
        $closed = Controller::request('closed');
        $page = Controller::request('page');
        $departmentId = Controller::request('departmentId');
        $title = Controller::request('title');
        $rpp = Controller::request('rpp');
        $offset = ($page-1)*($rpp*1);

        $condition = 'TRUE';
        $bindings = [];

        if($title) {
            $condition .= " AND title LIKE ?";
            $bindings[] = "%{$title}%";
        }

        if($departmentId) {
            $condition .= ' AND department_id = ?';
            $bindings[] = $departmentId;
        }

        if($closed) {
            $condition .= ' AND closed = ?';
            $bindings[] = 1;
        }

        $countTotal = $user->withCondition($condition, $bindings)->countShared('ticket');

        $condition .= ' LIMIT ?';
        $bindings[] = $rpp*1;
        $condition .= ' OFFSET ?';
        $bindings[] = $offset;

        //echo(" php.get-tickets: ".var_dump($condition));

        $tickets = $user->withCondition($condition, $bindings)->sharedTicketList->toArray(true);

        Response::respondSuccess([
            'tickets' => $tickets,
            'page' => $page,
            'pages' => ceil($countTotal / ($rpp*1))
        ]);
    }
}
