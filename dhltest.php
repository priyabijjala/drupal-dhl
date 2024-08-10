namespace Drupal\Tests\dhl_location_finder\Functional;

use Drupal\Tests\BrowserTestBase;

class LocationFinderFormTest extends BrowserTestBase {
  
  protected static $modules = ['dhl_location_finder'];

  public function testLocationFinderForm() {
    $this->drupalGet('/dhl-location-finder');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldExists('country');
    $this->assertSession()->fieldExists('city');
    $this->assertSession()->fieldExists('postal_code');

    $this->drupalPostForm('/dhl-location-finder', [
      'country' => 'Czechia',
      'city' => 'Prague',
      'postal_code' => '11000',
    ], 'Find Locations');

    $this->assertSession()->pageTextContains('Filtered Locations (YAML)');
  }
}
