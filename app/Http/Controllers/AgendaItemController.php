<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\AgendaItem;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AgendaItemController extends Controller
{
    public function store(Request $request, Meeting $meeting)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'duration_minutes' => 'required|integer|min:1',
            'presenter_id' => 'nullable|exists:users,id'
        ]);

        $agendaItem = $meeting->agenda()->create([
            ...$validated,
            'order' => $meeting->agenda()->count()
        ]);

        // Charger les relations nécessaires
        $agendaItem->load('presenter');

        return redirect()->back()->with('success', 'Point ajouté à l\'ordre du jour');
    }

    public function update(Request $request, AgendaItem $agendaItem)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'duration_minutes' => 'required|integer|min:1',
            'presenter_id' => 'nullable|exists:users,id'
        ]);

        $agendaItem->update($validated);

        // Charger les relations nécessaires
        $agendaItem->load('presenter');

        return redirect()->back()->with('success', 'Point mis à jour');
    }

    public function destroy(AgendaItem $agendaItem)
    {
        $agendaItem->delete();
        
        return redirect()->back()->with('success', 'Point supprimé');
    }

    public function reorder(Request $request, Meeting $meeting)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:agenda_items,id',
            'items.*.order' => 'required|integer|min:0'
        ]);

        foreach ($request->items as $item) {
            AgendaItem::where('id', $item['id'])->update(['order' => $item['order']]);
        }

        return redirect()->back()->with('success', 'Ordre mis à jour');
    }
} 