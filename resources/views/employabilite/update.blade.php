<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Update') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <form method="POST" action="{{ route('employabilite.update', $employabilite->id) }}">
                        @csrf
                        @method('PUT')

                        <div>
                            <x-label for="titre" :value="__('Titre')" />
                            <x-input id="titre" class="block mt-1 w-full" type="text" name="titre" :value="old('titre', $employabilite->titre)" required autofocus />
                        </div>

                        <div>












</x-app-layout>
