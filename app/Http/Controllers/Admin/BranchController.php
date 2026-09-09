<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BranchController extends Controller
{
    private function authorizeBranchManagement()
    {
        if (!Auth::user() || !Auth::user()->isSuperAdmin()) {
            abort(403, 'Only super admins can manage branches.');
        }
    }

    public function index()
    {
        $branches = Branch::all();
        return view('admin.branches.index', compact('branches'));
    }

    public function create()
    {
        $this->authorizeBranchManagement();
        return view('admin.branches.create');
    }

    public function store(Request $request)
    {
        $this->authorizeBranchManagement();

        $request->validate([
            'branch_code' => 'required|string|max:10|unique:branches',
            'branch_name' => 'required|string|max:255',
            'address' => 'required|string|max:500',
        ]);

        Branch::create($request->all());

        return redirect()->route('admin.branches.index')->with('success', 'Branch created successfully.');
    }

    public function show(Branch $branch)
    {
        return view('admin.branches.show', compact('branch'));
    }

    public function edit(Branch $branch)
    {
        $this->authorizeBranchManagement();
        return view('admin.branches.edit', compact('branch'));
    }

    public function update(Request $request, Branch $branch)
    {
        $this->authorizeBranchManagement();

        $request->validate([
            'branch_code' => 'required|string|max:10|unique:branches,branch_code,' . $branch->id,
            'branch_name' => 'required|string|max:255',
            'address' => 'required|string|max:500',
        ]);

        $branch->update($request->all());

        return redirect()->route('admin.branches.index')->with('success', 'Branch updated successfully.');
    }

    public function destroy(Branch $branch)
    {
        $this->authorizeBranchManagement();

        $branch->delete();

        return redirect()->route('admin.branches.index')->with('success', 'Branch deleted successfully.');
    }
}