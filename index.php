<?php

/**
 * Class FileSystemWalkService
 * Description: Данный класс реализует сервис по работе с файловой системой
 */
class FileSystemWalkService {

    /**
     * @var array
     * Description: Данное свойство регулирует kv-список директорий,
     * которые не должены учавствовать в цикле обхода папок.
     * По умолчанию хранит алиасы директорий linux
     */
    public $excludedFolders = [
        '.' => 'current folder',
        '..' => 'parent folder',
    ];

    /**
     * @return array
     * Description: Метод возвращает kv-список директорий,
     * которые не должены учавствовать в цикле обхода папок
     */
    public function getExcludedFolders(): array
    {
        return $this->excludedFolders;
    }

    /**
     * @param array $excludedFolders
     *   Массив key->value c названием пропускаемой директории и ее наименовнием
     * Description: Метод позволяет установить kv-список директорий,
     * которые не должены учавствовать в цикле обхода папок
     */
    public function setExcludedFolders(array $excludedFolders): void
    {
        $this->excludedFolders = $excludedFolders;
    }

    /**
     * @param string $path
     *   Путь до папки требующей обхода
     * @param bool $recursive
     *   Флаг включающий или отключающий рекурсивый обход папок. По умолчанию рекурсия включена
     * @param Closure|null $folderHandler
     *   Анонимная фукнция обработчик применяемая к каждой папке которую встретит итератор во время обхода.
     *   Первый параметр в анонимной фукнции будет объект класса SplFileInfo содержащий информацию о папке.
     * @param Closure|null $fileHandler
     *   Анонимная фукнция обработчик применяемая к каждому файлу который встретит итератор во время обхода.
     *   Первый параметр в анонимной фукнции будет объект класса SplFileInfo содержащий информацию о файле.
     * @throws Exception
     */
    public function walkByDirectory(
        string $path,
        bool $recursive = true,
        Closure $folderHandler = null,
        Closure $fileHandler = null
    ): void {
        if (!is_dir($path)) {
            throw new \RuntimeException('folder: ' . $path . ' not found');
        }
        $recursiveDirectoryIterator = new RecursiveDirectoryIterator($path);
        foreach ($recursiveDirectoryIterator as $filesystemObject) {
            if (array_key_exists($filesystemObject->getFileName(), $this->getExcludedFolders())) {
                continue;
            }
            if ($recursiveDirectoryIterator->hasChildren()) {
                $folder = $recursiveDirectoryIterator->getChildren();
                if ($folderHandler) {
                    $folderHandler($folder);
                }
                if ($recursive) {
                    $this->walkByDirectory(
                        $folder->getPathName(),
                        $recursive,
                        $folderHandler,
                        $fileHandler
                    );
                }
            } elseif ($fileHandler) {
                $fileHandler($filesystemObject);
            }
        }
    }
}

// Счетчик значений из всех файлов count
$count = 0;

/**
 * @param SplFileInfo $file
 * Description: Анонимная фукнция, обработчик файлов, которая ожидает что переданный файл
 * будет иметь имя count и прибавит его содержимое к переменной
 */
$countFileValuesCalculator = static function (SplFileInfo $file) use (&$count) {
    if ($file->getFilename() === 'count') {
        $path = $file->getPathname();
        $fileContent = file_get_contents($path);
        $count = bcadd((string) $count, (string) $fileContent);
    }
};

// Создаем новый экземпляр обьекта или вызываем его через DI
$fswService = new FileSystemWalkService();
try {
    // Указываем ему начальную папку для обхода и обработчик.
    $fswService->walkByDirectory('public/test', true, null, $countFileValuesCalculator);
} catch (\Exception $e) {
    // Если отловили исключение выводим его
    echo $e->getMessage();
    // Завершаем пришложение с ошибкой
    exit(1);
}
// Выводим результат
echo $count;
// Завершаем приложение
exit(0);
