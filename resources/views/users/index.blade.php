<x-app-layout :css="['components/pagination.css', 'users/index.css']">
    <div class="users">
        <div class="users__form-layout">
            <form class="users__search-form" action="/users" method="get">
                <input type="search" name="search" placeholder="検索キーワード" value="{{ request()->query('search') }}" />
                <button type="submit">
                    <img src="{{ asset('img/search.svg') }}" alt="search" />
                </button>
            </form>
        </div>

        <div class="users__pagination">
            {{ $users->links() }}
        </div>

        <div class="users__names">
            @foreach ($users as $user)
                <a href="{{ route('users.show', $user) }}">{{ $user->name }}</a>
            @endforeach
        </div>
    </div>
</x-app-layout>
