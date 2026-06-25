<?php

namespace App\Http\Controllers;

use App\Models\CatatanRapat;
use App\Models\CatatanRapatLog;
use App\Models\KanbanColumn;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CatatanRapatController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('pamong.permission:catatan_rapat,view')->only(['index']);
        $this->middleware('pamong.permission:catatan_rapat,create')->only(['store']);
        $this->middleware('pamong.permission:catatan_rapat,edit')->only(['update', 'move']);
        $this->middleware('pamong.permission:catatan_rapat,delete')->only(['destroy']);
    }

    public function index()
    {
        $columns = KanbanColumn::with(['cards.creator', 'cards.assignee'])
            ->orderBy('order')
            ->get();

        $users = User::query()
            ->whereHas('role', fn ($query) => $query->whereIn('name', User::attendanceRoleNames()))
            ->orderBy('username')
            ->get();
        $canCreate = $this->canCreate();
        $settings = $this->getSettings();

        // Fetch recent activity logs
        $logs = CatatanRapatLog::with('user')
            ->orderByDesc('created_at')
            ->take(50)
            ->get();

        return view('catatan-rapat.index', compact('columns', 'users', 'canCreate', 'settings', 'logs'));
    }

    public function store(Request $request)
    {
        if (!$this->canCreate()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'tanggal_rapat' => 'nullable|date',
            'column_id' => 'required|exists:kanban_columns,id',
            'assigned_to' => 'nullable|exists:users,id',
            'priority' => 'in:low,medium,high',
            'due_date' => 'nullable|date',
        ]);

        $validated['created_by'] = Auth::id();
        $validated['order'] = CatatanRapat::where('column_id', $validated['column_id'])->max('order') + 1;

        $card = CatatanRapat::create($validated);
        $card->load(['creator', 'assignee', 'column']);

        // Log activity
        $column = KanbanColumn::find($validated['column_id']);
        CatatanRapatLog::log($card, 'created', [
            'column' => $column->name ?? '-',
            'priority' => $validated['priority'] ?? 'medium',
            'assigned_to' => $card->assignee?->username,
        ]);

        return response()->json($card);
    }

    public function update(Request $request, CatatanRapat $catatanRapat)
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'tanggal_rapat' => 'nullable|date',
            'column_id' => 'sometimes|exists:kanban_columns,id',
            'assigned_to' => 'nullable|exists:users,id',
            'priority' => 'in:low,medium,high',
            'due_date' => 'nullable|date',
            'order' => 'sometimes|integer',
        ]);

        // Track what changed
        $changes = [];
        foreach (['title', 'description', 'priority', 'due_date', 'assigned_to'] as $field) {
            if (isset($validated[$field]) && $catatanRapat->$field != $validated[$field]) {
                $changes[$field] = [
                    'from' => $catatanRapat->$field,
                    'to' => $validated[$field],
                ];
            }
        }

        // Track column change
        if (isset($validated['column_id']) && $catatanRapat->column_id != $validated['column_id']) {
            $oldColumn = KanbanColumn::find($catatanRapat->column_id);
            $newColumn = KanbanColumn::find($validated['column_id']);
            $changes['column'] = [
                'from' => $oldColumn->name ?? '-',
                'to' => $newColumn->name ?? '-',
            ];
        }

        $catatanRapat->update($validated);
        $catatanRapat->load(['creator', 'assignee', 'column']);

        // Log activity if there are changes
        if (!empty($changes)) {
            CatatanRapatLog::log($catatanRapat, 'updated', $changes);
        }

        return response()->json($catatanRapat);
    }

    public function destroy(CatatanRapat $catatanRapat)
    {
        // Log before deleting
        CatatanRapatLog::log($catatanRapat, 'deleted', [
            'column' => $catatanRapat->column?->name ?? '-',
        ]);

        $catatanRapat->delete();
        return response()->json(['success' => true]);
    }

    public function move(Request $request)
    {
        $validated = $request->validate([
            'card_id' => 'required|exists:catatan_rapat,id',
            'column_id' => 'required|exists:kanban_columns,id',
            'order' => 'required|integer',
        ]);

        $card = CatatanRapat::find($validated['card_id']);
        $oldColumnId = $card->column_id;
        $newColumnId = $validated['column_id'];
        $newOrder = $validated['order'];

        // Track column change for logging
        $oldColumn = KanbanColumn::find($oldColumnId);
        $newColumn = KanbanColumn::find($newColumnId);

        DB::transaction(function () use ($card, $oldColumnId, $newColumnId, $newOrder) {
            // If moving to different column
            if ($oldColumnId != $newColumnId) {
                // Reorder old column
                CatatanRapat::where('column_id', $oldColumnId)
                    ->where('order', '>', $card->order)
                    ->decrement('order');

                // Make space in new column
                CatatanRapat::where('column_id', $newColumnId)
                    ->where('order', '>=', $newOrder)
                    ->increment('order');
            } else {
                // Same column reorder
                if ($newOrder > $card->order) {
                    CatatanRapat::where('column_id', $newColumnId)
                        ->where('order', '>', $card->order)
                        ->where('order', '<=', $newOrder)
                        ->decrement('order');
                } else {
                    CatatanRapat::where('column_id', $newColumnId)
                        ->where('order', '>=', $newOrder)
                        ->where('order', '<', $card->order)
                        ->increment('order');
                }
            }

            $card->update([
                'column_id' => $newColumnId,
                'order' => $newOrder,
            ]);
        });

        // Log the move
        if ($oldColumnId != $newColumnId) {
            CatatanRapatLog::log($card, 'moved', [
                'from_column' => $oldColumn->name ?? '-',
                'to_column' => $newColumn->name ?? '-',
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function updateSettings(Request $request)
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'can_create_roles' => 'required|array',
            'can_create_roles.*' => 'in:admin,teacher,pkg_manager',
        ]);

        DB::table('catatan_rapat_settings')
            ->where('key', 'can_create_roles')
            ->update(['value' => json_encode($validated['can_create_roles']), 'updated_at' => now()]);

        return response()->json(['success' => true]);
    }

    private function canCreate(): bool
    {
        $user = Auth::user();
        $settings = $this->getSettings();
        $allowedRoles = json_decode($settings['can_create_roles'] ?? '["admin","teacher","pkg_manager"]', true);

        if ($user->isAdmin() && in_array(User::ROLE_ADMIN, $allowedRoles, true)) {
            return true;
        }

        if ($user->isTeacher() && in_array(User::ROLE_TEACHER, $allowedRoles, true)) {
            return $user->hasPamongCrudPermission('catatan_rapat', 'create');
        }

        if ($user->isPengurusPkg() && in_array(User::ROLE_PKG_MANAGER, $allowedRoles, true)) {
            return $user->hasPamongCrudPermission('catatan_rapat', 'create');
        }

        return false;
    }

    private function getSettings(): array
    {
        return DB::table('catatan_rapat_settings')
            ->pluck('value', 'key')
            ->toArray();
    }
}
