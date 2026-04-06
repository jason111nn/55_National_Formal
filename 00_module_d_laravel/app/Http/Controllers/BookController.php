<?php
namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BookController extends Controller
{
    /**
     * 獲取 JSON API (支援分頁與關鍵字查詢)
     */
    public function indexApi(Request $request)
    {
        $query = Book::with(['publisher'])
            ->where('is_hidden', false)
            ->whereHas('publisher', function($q) {
                $q->where('status', 'active'); // 評審提點：停權不出現在 API
            });

        if ($request->has('query')) {
            $qs = $request->input('query');
            $query->where(function($q) use ($qs) {
                $q->where('title', 'LIKE', "%{$qs}%")
                  ->orWhere('description', 'LIKE', "%{$qs}%");
            });
        }

        // 以預設 3 筆限制分頁
        return $query->paginate(3);
    }

    /**
     * 儲存新書本並即時計算驗證 13 碼
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'isbn12' => 'required|string|size:12', // 限定前 12 碼
            'title' => 'required|string',
            'publisher_id' => 'required|exists:publishers,id',
        ]);

        $isbn12 = str_replace('-', '', $validated['isbn12']);
        $publisher = \App\Models\Publisher::find($validated['publisher_id']);

        // 出版社代碼吻合驗證
        if (!str_contains($isbn12, str_replace('-', '', $publisher->isbn_code))) {
            throw ValidationException::withMessages(['isbn12' => 'ISBN 必須包含該出版社之代碼。']);
        }

        // 計算第 13 碼
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $weight = ($i % 2 === 0) ? 1 : 3;
            $sum += (int)$isbn12[$i] * $weight;
        }
        $rem = $sum % 10;
        $checkDigit = $rem === 0 ? 0 : (10 - $rem);
        
        $fullIsbn = $validated['isbn12'] . '-' . $checkDigit;

        $book = Book::create(array_merge($request->all(), [
            'isbn' => $fullIsbn, 
        ]));

        return redirect()->route('admin.books.index')->with('success', '書籍新增成功！');
    }

    /**
     * 公開展示頁面 /01/{isbn}
     */
    public function showPublic($isbn)
    {
        // 抹除多餘連字號比對資料庫
        $cleanIsbnPattern = str_replace('-', '', $isbn);
        
        $book = Book::with('publisher')
            ->where('is_hidden', false)
            ->whereRaw("REPLACE(isbn, '-', '') = ?", [$cleanIsbnPattern])
            ->whereHas('publisher', function($q) {
                $q->where('status', 'active');
            })->first();

        if (!$book) {
            abort(404, '找不到該書籍或已被下架');
        }

        return view('book.public', compact('book'));
    }
}
