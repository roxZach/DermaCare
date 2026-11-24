<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** Manage skin-care articles shown to Guests. */
class ArticleController extends Controller
{
    public function index()
    {
        return view('admin.articles.index', ['articles' => Article::latest()->get()]);
    }

    public function create()
    {
        return view('admin.articles.form', ['article' => new Article(['is_published' => true])]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = Str::slug($data['title']) . '-' . Str::lower(Str::random(4));
        $a = Article::create($data);
        AuditLog::record('article_created', $a->title);
        return redirect()->route('admin.articles.index')->with('success', 'Article saved.');
    }

    public function edit(Article $article)
    {
        return view('admin.articles.form', compact('article'));
    }

    public function update(Request $request, Article $article)
    {
        $article->update($this->validated($request));
        AuditLog::record('article_updated', $article->title);
        return redirect()->route('admin.articles.index')->with('success', 'Article updated.');
    }

    public function destroy(Article $article)
    {
        AuditLog::record('article_deleted', $article->title);
        $article->delete();
        return back()->with('success', 'Article deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:200',
            'summary' => 'nullable|string|max:255',
            'body' => 'required|string',
        ]) + ['is_published' => $request->boolean('is_published')];
    }
}
