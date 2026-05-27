<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentFolderRequest;
use App\Models\DocumentFolder;
use Illuminate\Http\RedirectResponse;

class DocumentFolderController extends Controller
{
    public function store(StoreDocumentFolderRequest $request): RedirectResponse
    {
        $folder = DocumentFolder::query()->create([
            'name' => $request->validated('name'),
            'created_by_user_id' => $request->user()->id,
        ]);

        return redirect()
            ->route('documents.index', ['folder' => $folder->id])
            ->with('status', __('documents.folder_created'));
    }

    public function destroy(DocumentFolder $folder): RedirectResponse
    {
        $this->authorize('delete', $folder);

        abort_if($folder->documents()->exists(), 422);

        $folder->delete();

        return redirect()
            ->route('documents.index')
            ->with('status', __('documents.folder_deleted'));
    }
}
