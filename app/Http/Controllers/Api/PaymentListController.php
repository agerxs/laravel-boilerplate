<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentListResource;
use App\Models\MeetingPaymentList;
use Illuminate\Http\Request;
use App\Http\Utils\Constants;


class PaymentListController extends Controller
{
    public function index(Request $request)
    {
        $query = MeetingPaymentList::with(['meeting']);

        // Filtres
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('meeting_id')) {
            $query->where('meeting_id', $request->meeting_id);
        }
        if ($request->has('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->has('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        // Tri
        if ($request->has('sort_by')) {
            $direction = $request->get('sort_dir', 'desc');
            $query->orderBy($request->sort_by, $direction);
        } else {
            $query->orderBy('created_at', 'desc');
        }
        $lists = $query->paginate(20);

        return $this->format(Constants::JSON_STATUS_SUCCESS, 200, 'Liste des réunions récupérée avec succès', [
            'payments' => PaymentListResource::collection($lists),
        ]);
    }

    public function show($id)
    {
        $list = MeetingPaymentList::with(['meeting', 'items'])->findOrFail($id);
        return new PaymentListResource($list->load('items'));
    }
} 