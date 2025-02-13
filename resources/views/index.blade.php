<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eFaktur CSV converter</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen dark:bg-slate-700">
    <div class="bg-white p-8 rounded-lg shadow-lg dark:bg-black">
        <!-- Alert Box (initially hidden) -->
        <div id="alertBox" class="hidden p-4 mb-4 text-sm text-green-700 bg-green-100 rounded-lg" role="alert">
            <span class="font-medium">Success!</span> File has been selected.
        </div>
        <form action="{{route('upload')}}" method="post" enctype="multipart/form-data">
            @csrf
            <div class="mb-4">
                <h1 class="text-3xl font-extrabold dark:text-white">Convert eFaktur CSV to Coretax Excel.</h1>
            </div>
            <div class="mb-4">
                <label for="file-upload" class="block text-sm font-medium text-gray-700 dark:text-white">Choose a file</label>
                <input type="file" id="file-upload" name="file" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:text-white">
            </div>
            <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Submit
            </button>
        </form>
    </div>
</body>
</html>