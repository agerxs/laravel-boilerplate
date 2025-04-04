                    <NavLink :href="route('representatives.index')" :active="route().current('representatives.*')" v-if="$page.props.auth.user && !$page.props.auth.user.hasRole(['admin', 'Admin', 'gestionnaire', 'Gestionnaire'])">
                        Représentants
                    </NavLink> 