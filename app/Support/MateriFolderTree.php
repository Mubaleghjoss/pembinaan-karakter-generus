<?php

namespace App\Support;

use App\Models\Materi;
use App\Models\MateriFolder;
use Illuminate\Support\Collection;

class MateriFolderTree
{
    public const MAIN_FOLDERS = [
        [
            'name' => 'PKG',
            'description' => 'Materi 29 karakter luhur.',
            'sort_order' => 1,
        ],
        [
            'name' => 'PPG',
            'description' => 'Folder materi PPG yang diisi manual oleh admin.',
            'sort_order' => 2,
        ],
        [
            'name' => 'RPP Target Generus SMP SMA',
            'description' => 'Folder RPP target generus SMP dan SMA yang diisi manual oleh admin.',
            'sort_order' => 3,
        ],
    ];

    public const PKG_CHARACTER_FOLDERS = [
        'Akhlaqul Karimah',
        'Alim Faqih',
        'Mandiri',
        'Rukun',
        'Kompak',
        'Kerjasama yang baik',
        'Jujur',
        'Amanah',
        'Mujhid Muzhid',
        'Bersyukur',
        'Mempersungguh',
        'Mengagungkan',
        'Berdoa',
        'Benar',
        'Kurup',
        'Janji',
        'Syukur atas nikmat',
        "Istirja' saat musibah",
        'Sabar dalam cobaab',
        'Bertaubat atas kesalahan',
        'Yang kuat membantu yang lemah',
        'Yang bisa membantu yang belum bisa',
        'Yang ingat mengingatkan yang lupa',
        'Yang salah dinasehati agar bertaubat',
        'Bicara yang baik dan benar',
        'Jujur dan saling percaya',
        'sabar dan keporo ngalah',
        'Tidak menyakiti / merusak sesama',
        'Saling memperhatikan & menjaga perasaan',
    ];

    public function folderTree(
        bool $includeInactiveFolders = false,
        bool $includeInactiveMateri = false,
        bool $includeEmptyRoots = true,
        bool $includeUnfiled = true
    ): Collection {
        $folders = MateriFolder::query()
            ->when(! $includeInactiveFolders, fn ($query) => $query->active())
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $folderIds = $folders->pluck('id');
        $materiByFolder = Materi::query()
            ->whereIn('materi_folder_id', $folderIds)
            ->when(! $includeInactiveMateri, fn ($query) => $query->active())
            ->orderByDesc('bulan')
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('materi_folder_id');

        $folders->each(function (MateriFolder $folder) use ($materiByFolder) {
            $materi = $materiByFolder->get($folder->id, collect())->values();
            $folder->setRelation('materi', $materi);
            $folder->setAttribute('materi_count', $materi->count());
        });

        $foldersByParent = $folders->groupBy(fn (MateriFolder $folder) => (string) ($folder->parent_id ?: 0));

        $attachChildren = function (MateriFolder $folder) use (&$attachChildren, $foldersByParent): MateriFolder {
            $children = $foldersByParent
                ->get((string) $folder->id, collect())
                ->values()
                ->map(fn (MateriFolder $child) => $attachChildren($child));

            $totalMateriCount = (int) $folder->getAttribute('materi_count')
                + $children->sum(fn (MateriFolder $child) => (int) $child->getAttribute('total_materi_count'));

            $folder->setRelation('childrenTree', $children);
            $folder->setAttribute('total_materi_count', $totalMateriCount);

            return $folder;
        };

        $rootIds = $folders->pluck('id')->all();
        $roots = $folders
            ->filter(fn (MateriFolder $folder) => ! $folder->parent_id || ! in_array((int) $folder->parent_id, $rootIds, true))
            ->values()
            ->map(fn (MateriFolder $folder) => $attachChildren($folder))
            ->when(! $includeEmptyRoots, fn (Collection $items) => $items->filter(
                fn (MateriFolder $folder) => (int) $folder->getAttribute('total_materi_count') > 0
            ))
            ->values();

        if ($includeUnfiled) {
            $unfiledMateri = Materi::query()
                ->whereNull('materi_folder_id')
                ->when(! $includeInactiveMateri, fn ($query) => $query->active())
                ->orderByDesc('bulan')
                ->orderByDesc('created_at')
                ->get();

            if ($unfiledMateri->isNotEmpty()) {
                $folder = new MateriFolder([
                    'name' => 'Tanpa Folder',
                    'description' => 'Materi yang belum dikelompokkan.',
                    'sort_order' => 999999,
                    'is_active' => true,
                ]);
                $folder->setRelation('materi', $unfiledMateri);
                $folder->setRelation('childrenTree', collect());
                $folder->setAttribute('materi_count', $unfiledMateri->count());
                $folder->setAttribute('total_materi_count', $unfiledMateri->count());
                $roots->push($folder);
            }
        }

        return $roots;
    }

    public function folderOptions(bool $includeInactiveFolders = false): Collection
    {
        return $this->flattenFolders($this->folderTree($includeInactiveFolders, true, true, false));
    }

    public function folderAndDescendantIds(int $folderId, bool $activeOnly = true): array
    {
        $folders = MateriFolder::query()
            ->when($activeOnly, fn ($query) => $query->active())
            ->get(['id', 'parent_id']);

        if (! $folders->contains('id', $folderId)) {
            return [$folderId];
        }

        $childrenByParent = $folders->groupBy(fn (MateriFolder $folder) => (string) ($folder->parent_id ?: 0));
        $ids = [$folderId];
        $walk = function (int $parentId) use (&$walk, &$ids, $childrenByParent): void {
            foreach ($childrenByParent->get((string) $parentId, collect()) as $child) {
                $ids[] = (int) $child->id;
                $walk((int) $child->id);
            }
        };

        $walk($folderId);

        return array_values(array_unique($ids));
    }

    private function flattenFolders(Collection $folders, string $prefix = ''): Collection
    {
        return $folders->flatMap(function (MateriFolder $folder) use ($prefix) {
            $folder->setAttribute('display_name', $prefix . $folder->name);

            return collect([$folder])->merge(
                $this->flattenFolders($folder->childrenTree ?? collect(), $prefix . $folder->name . ' / ')
            );
        })->values();
    }
}
