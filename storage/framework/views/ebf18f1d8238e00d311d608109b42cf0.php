<?php
    use App\Helpers\MenuHelper;
    use App\Models\Profile;
    $menuGroups = MenuHelper::getMenuGroups();

    // Get current path
    $currentPath = request()->path();
    $profileName = null;
    if (auth()->check() && auth()->user()->profile_id) {
        $profileName = Profile::where('id', auth()->user()->profile_id)->value('name');
    }
?>

<aside id="sidebar"
    class="fixed flex flex-col mt-0 top-0 px-5 left-0 bg-white dark:bg-gray-900 dark:border-gray-800 text-gray-900 h-screen transition-all duration-300 ease-in-out z-99999 border-r border-gray-200"
    x-data="{
        openSubmenus: {},
        init() {
            this.initializeActiveMenus();
        },
        initializeActiveMenus() {
            <?php $__currentLoopData = $menuGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $groupIndex => $menuGroup): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php $__currentLoopData = $menuGroup['items']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $itemIndex => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php if(!empty($item['subItems'])): ?>
                        <?php $__currentLoopData = $item['subItems']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $subItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            if (this.isActive('<?php echo e($subItem['path']); ?>')) {
                                this.openSubmenus['<?php echo e($groupIndex); ?>-<?php echo e($itemIndex); ?>'] = true;
                            }
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php endif; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        },
        keepSubmenuOpen(groupIndex, itemIndex) {
            const key = groupIndex + '-' + itemIndex;
            this.openSubmenus[key] = true;
            localStorage.setItem('sidebarOpenSubmenus', JSON.stringify(this.openSubmenus));
        },
        toggleSubmenu(groupIndex, itemIndex) {
            const key = groupIndex + '-' + itemIndex;
            const newState = !this.openSubmenus[key];

            if (newState) {
                // Opcional: Cerrar otros al abrir uno nuevo
                // this.openSubmenus = {}; 
            }

            this.openSubmenus[key] = newState;
            localStorage.setItem('sidebarOpenSubmenus', JSON.stringify(this.openSubmenus));
        },
        isSubmenuOpen(groupIndex, itemIndex) {
            const key = groupIndex + '-' + itemIndex;
            return this.openSubmenus[key] || false;
        },
        normalizePath(path) {
            if (!path) return '';
            try {
                if (path.startsWith('http')) {
                    path = new URL(path).pathname;
                }
            } catch (e) {}
            path = path.split('?')[0].split('#')[0];
            const normalized = path.replace(/\/+$/, '');
            return normalized === '' ? '/' : normalized;
        },
        isActiveExact(path) {
            const current = this.normalizePath(window.location.pathname);
            const target = this.normalizePath(path);
            return current === target;
        },
        isActive(path) {
            const current = this.normalizePath(window.location.pathname);
            const target = this.normalizePath(path);
            if (target === '/') return current === '/';
            return current === target || current.startsWith(target + '/');
        }
    }"
    :class="{
        'w-[290px]': $store.sidebar.isExpanded || $store.sidebar.isMobileOpen || $store.sidebar.isHovered,
        'w-[90px]': !$store.sidebar.isExpanded && !$store.sidebar.isHovered,
        'translate-x-0': $store.sidebar.isMobileOpen,
        '-translate-x-full xl:translate-x-0': !$store.sidebar.isMobileOpen
    }"
    @mouseenter="if (!$store.sidebar.isExpanded) $store.sidebar.setHovered(true)"
    @mouseleave="$store.sidebar.setHovered(false)">

    <div class="pt-8 pb-7 flex"
        :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ?
        'xl:justify-center' :
        'justify-start'">
        <a href="/">
            <img x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                class="dark:hidden" src="/images/logo/Xinergia.png" alt="Logo" width="150" height="40" />
            <img x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                class="hidden dark:block" src="/images/logo/Xinergia.png" alt="Logo" width="150"
                height="40" />
            <img x-show="!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen"
                src="/images/logo/Xinergia-icon.png" alt="Logo" width="32" height="32" />
        </a>
    </div>

    <div class="flex flex-col overflow-y-auto duration-300 ease-linear no-scrollbar">
        <nav class="mb-6">
            <div class="flex flex-col gap-4">
                <?php $__currentLoopData = $menuGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $groupIndex => $menuGroup): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div>
                        <h2 class="mb-4 text-xs uppercase flex leading-[20px] text-gray-400"
                            :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ?
                            'lg:justify-center' : 'justify-start'">
                            <template
                                x-if="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen">
                                <span><?php echo e($profileName ?? $menuGroup['title']); ?></span>
                            </template>
                            <template x-if="!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M5.99915 10.2451C6.96564 10.2451 7.74915 11.0286 7.74915 11.9951V12.0051C7.74915 12.9716 6.96564 13.7551 5.99915 13.7551C5.03265 13.7551 4.24915 12.9716 4.24915 12.0051V11.9951C4.24915 11.0286 5.03265 10.2451 5.99915 10.2451ZM17.9991 10.2451C18.9656 10.2451 19.7491 11.0286 19.7491 11.9951V12.0051C19.7491 12.9716 18.9656 13.7551 17.9991 13.7551C17.0326 13.7551 16.2491 12.9716 16.2491 12.0051V11.9951C16.2491 11.0286 17.0326 10.2451 17.9991 10.2451ZM13.7491 11.9951C13.7491 11.0286 12.9656 10.2451 11.9991 10.2451C11.0326 10.2451 10.2491 11.0286 10.2491 11.9951V12.0051C10.2491 12.9716 11.0326 13.7551 11.9991 13.7551C12.9656 13.7551 13.7491 12.9716 13.7491 12.0051V11.9951Z" fill="currentColor"/>
                                </svg>
                            </template>
                        </h2>

                        <ul class="flex flex-col gap-1">
                            <?php $__currentLoopData = $menuGroup['items']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $itemIndex => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li>
                                    
                                    <?php if(!empty($item['subItems'])): ?>
                                        
                                        <button @click="toggleSubmenu(<?php echo e($groupIndex); ?>, <?php echo e($itemIndex); ?>)"
                                            class="menu-item group w-full"
                                            :class="[
                                                isSubmenuOpen(<?php echo e($groupIndex); ?>, <?php echo e($itemIndex); ?>) ?
                                                'menu-item-active' : 'menu-item-inactive',
                                                !$store.sidebar.isExpanded && !$store.sidebar.isHovered ?
                                                'xl:justify-center' : 'xl:justify-start'
                                            ]">

                                            <span :class="isSubmenuOpen(<?php echo e($groupIndex); ?>, <?php echo e($itemIndex); ?>) ?
                                                    'menu-item-icon-active' : 'menu-item-icon-inactive'">
                                                <?php echo $item['icon']; ?>

                                            </span>

                                            <span
                                                x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                                                class="menu-item-text flex items-center gap-2">
                                                <?php echo e($item['name']); ?>

                                                
                                                <?php if(!empty($item['new'])): ?>
                                                    <span class="absolute right-10" :class="isActive('<?php echo e($item['path'] ?? ''); ?>') ? 'menu-dropdown-badge menu-dropdown-badge-active' : 'menu-dropdown-badge menu-dropdown-badge-inactive'">new</span>
                                                <?php endif; ?>
                                            </span>

                                            <svg x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                                                class="ml-auto w-5 h-5 transition-transform duration-200"
                                                :class="{
                                                    'rotate-180 text-brand-500': isSubmenuOpen(<?php echo e($groupIndex); ?>, <?php echo e($itemIndex); ?>)
                                                }"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        </button>

                                        <div x-show="isSubmenuOpen(<?php echo e($groupIndex); ?>, <?php echo e($itemIndex); ?>) && ($store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen)">
                                            <ul class="mt-2 space-y-1 ml-9">
                                                <?php $__currentLoopData = $item['subItems']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $subItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <li>
                                                        <a href="<?php echo e($subItem['path']); ?>" 
                                                            @click="keepSubmenuOpen(<?php echo e($groupIndex); ?>, <?php echo e($itemIndex); ?>)"
                                                            class="menu-dropdown-item"
                                                            :class="isActiveExact('<?php echo e($subItem['path']); ?>') ?
                                                                'menu-dropdown-item-active' :
                                                                'menu-dropdown-item-inactive'">
                                                            <?php echo e($subItem['name']); ?>

                                                            <span class="flex items-center gap-1 ml-auto">
                                                                <?php if(!empty($subItem['new'])): ?>
                                                                    <span :class="isActive('<?php echo e($subItem['path']); ?>') ? 'menu-dropdown-badge menu-dropdown-badge-active' : 'menu-dropdown-badge menu-dropdown-badge-inactive'">new</span>
                                                                <?php endif; ?>
                                                                <?php if(!empty($subItem['pro'])): ?>
                                                                    <span :class="isActive('<?php echo e($subItem['path']); ?>') ? 'menu-dropdown-badge-pro menu-dropdown-badge-pro-active' : 'menu-dropdown-badge-pro menu-dropdown-badge-pro-inactive'">pro</span>
                                                                <?php endif; ?>
                                                            </span>
                                                        </a>
                                                    </li>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </ul>
                                        </div>

                                    <?php else: ?>
                                        
                                        <a href="<?php echo e($item['path']); ?>" 
                                           class="menu-item group w-full"
                                           :class="[
                                                isActive('<?php echo e($item['path']); ?>') ? 'menu-item-active' : 'menu-item-inactive',
                                                !$store.sidebar.isExpanded && !$store.sidebar.isHovered ?
                                                'xl:justify-center' : 'xl:justify-start'
                                           ]">

                                            <span :class="isActive('<?php echo e($item['path']); ?>') ?
                                                    'menu-item-icon-active' : 'menu-item-icon-inactive'">
                                                <?php echo $item['icon']; ?>

                                            </span>

                                            <span
                                                x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                                                class="menu-item-text flex items-center gap-2">
                                                <?php echo e($item['name']); ?>

                                                
                                                <?php if(!empty($item['new'])): ?>
                                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-brand-500 text-white">new</span>
                                                <?php endif; ?>
                                            </span>
                                        </a>

                                    <?php endif; ?>
                                </li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ul>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </nav>
    </div>
</aside>

<div x-show="$store.sidebar.isMobileOpen" @click="$store.sidebar.setMobileOpen(false)"
    class="fixed z-50 h-screen w-full bg-gray-900/50"></div>
<?php /**PATH C:\laragon\www\Restaurante\resources\views/layouts/sidebar.blade.php ENDPATH**/ ?>