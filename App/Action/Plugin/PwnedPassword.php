<?php
/**
 * See LICENSE.txt for license details.
 */

namespace MageWare\Hibp\App\Action\Plugin;

class PwnedPassword
{
    /**
     * @var \Magento\Backend\Model\Auth
     */
    private $auth;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $messageManager;

    /**
     * @var \MageWare\Hibp\Model\PwnedPasswordInterface
     */
    private $pwnedPasswordService;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Construct
     *
     * @param \Magento\Backend\Model\Auth $auth
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \MageWare\Hibp\Model\PwnedPasswordInterface $pwnedPasswordService
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Backend\Model\Auth $auth,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \MageWare\Hibp\Model\PwnedPasswordInterface $pwnedPasswordService,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->auth = $auth;
        $this->messageManager = $messageManager;
        $this->pwnedPasswordService = $pwnedPasswordService;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param \Magento\Backend\App\AbstractAction $subject
     * @param \Closure $proceed
     * @param \Magento\Framework\App\RequestInterface $request
     *
     * @return mixed
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundDispatch(
        \Magento\Backend\App\AbstractAction $subject,
        \Closure $proceed,
        \Magento\Framework\App\RequestInterface $request
    ) {
        $checkPwnedPassword = false;
        $login = $request->getPost('login');
        if (!$this->auth->isLoggedIn()) {
            if (!empty($login['password'])) {
                $checkPwnedPassword = $this->scopeConfig->isSetFlag('mageware_hibp/admin/check_pwned_password');
            }
        }
        $result = $proceed($request);
        if ($checkPwnedPassword) {
            if ($this->auth->isLoggedIn()) {
                if (!empty($login['password'])) {
                    if ($this->pwnedPasswordService->isPwned($login['password'])) {
                        $this->messageManager->addWarning(
                            __('Your password is pwned. Learn more <a href="https://haveibeenpwned.com/Passwords">here</a>.')
                        );
                    }
                }
            }
        }
        return $result;
    }
}
