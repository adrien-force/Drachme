import DashboardController from './DashboardController'
import AccountController from './AccountController'
import ShellPlaceholderController from './ShellPlaceholderController'
import ImportProviderController from './ImportProviderController'
import ImportController from './ImportController'
import Settings from './Settings'

const Controllers = {
    DashboardController: Object.assign(DashboardController, DashboardController),
    AccountController: Object.assign(AccountController, AccountController),
    ShellPlaceholderController: Object.assign(ShellPlaceholderController, ShellPlaceholderController),
    ImportProviderController: Object.assign(ImportProviderController, ImportProviderController),
    ImportController: Object.assign(ImportController, ImportController),
    Settings: Object.assign(Settings, Settings),
}

export default Controllers