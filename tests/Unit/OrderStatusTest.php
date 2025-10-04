<?php

namespace Tests\Unit;

use App\Enums\OrderStatus;
use PHPUnit\Framework\TestCase;

class OrderStatusTest extends TestCase
{
    /** @test */
    public function it_has_all_expected_statuses()
    {
        $expectedStatuses = ['draft', 'sent', 'confirmed', 'received', 'cancelled'];

        foreach ($expectedStatuses as $status) {
            $this->assertNotNull(OrderStatus::tryFrom($status), "Status '{$status}' should exist");
        }
    }

    /** @test */
    public function draft_can_transition_to_sent()
    {
        $this->assertTrue(OrderStatus::DRAFT->canTransitionTo(OrderStatus::SENT));
    }

    /** @test */
    public function draft_can_transition_to_cancelled()
    {
        $this->assertTrue(OrderStatus::DRAFT->canTransitionTo(OrderStatus::CANCELLED));
    }

    /** @test */
    public function draft_cannot_transition_to_confirmed()
    {
        $this->assertFalse(OrderStatus::DRAFT->canTransitionTo(OrderStatus::CONFIRMED));
    }

    /** @test */
    public function draft_cannot_transition_to_received()
    {
        $this->assertFalse(OrderStatus::DRAFT->canTransitionTo(OrderStatus::RECEIVED));
    }

    /** @test */
    public function sent_can_transition_to_confirmed()
    {
        $this->assertTrue(OrderStatus::SENT->canTransitionTo(OrderStatus::CONFIRMED));
    }

    /** @test */
    public function sent_can_transition_to_cancelled()
    {
        $this->assertTrue(OrderStatus::SENT->canTransitionTo(OrderStatus::CANCELLED));
    }

    /** @test */
    public function sent_cannot_transition_to_draft()
    {
        $this->assertFalse(OrderStatus::SENT->canTransitionTo(OrderStatus::DRAFT));
    }

    /** @test */
    public function sent_cannot_transition_to_received()
    {
        $this->assertFalse(OrderStatus::SENT->canTransitionTo(OrderStatus::RECEIVED));
    }

    /** @test */
    public function confirmed_can_transition_to_received()
    {
        $this->assertTrue(OrderStatus::CONFIRMED->canTransitionTo(OrderStatus::RECEIVED));
    }

    /** @test */
    public function confirmed_can_transition_to_cancelled()
    {
        $this->assertTrue(OrderStatus::CONFIRMED->canTransitionTo(OrderStatus::CANCELLED));
    }

    /** @test */
    public function confirmed_cannot_transition_to_draft()
    {
        $this->assertFalse(OrderStatus::CONFIRMED->canTransitionTo(OrderStatus::DRAFT));
    }

    /** @test */
    public function confirmed_cannot_transition_to_sent()
    {
        $this->assertFalse(OrderStatus::CONFIRMED->canTransitionTo(OrderStatus::SENT));
    }

    /** @test */
    public function received_is_final_state()
    {
        $this->assertFalse(OrderStatus::RECEIVED->canTransitionTo(OrderStatus::DRAFT));
        $this->assertFalse(OrderStatus::RECEIVED->canTransitionTo(OrderStatus::SENT));
        $this->assertFalse(OrderStatus::RECEIVED->canTransitionTo(OrderStatus::CONFIRMED));
        $this->assertFalse(OrderStatus::RECEIVED->canTransitionTo(OrderStatus::CANCELLED));
    }

    /** @test */
    public function cancelled_is_final_state()
    {
        $this->assertFalse(OrderStatus::CANCELLED->canTransitionTo(OrderStatus::DRAFT));
        $this->assertFalse(OrderStatus::CANCELLED->canTransitionTo(OrderStatus::SENT));
        $this->assertFalse(OrderStatus::CANCELLED->canTransitionTo(OrderStatus::CONFIRMED));
        $this->assertFalse(OrderStatus::CANCELLED->canTransitionTo(OrderStatus::RECEIVED));
    }

    /** @test */
    public function status_cannot_transition_to_itself()
    {
        foreach (OrderStatus::cases() as $status) {
            $this->assertFalse(
                $status->canTransitionTo($status),
                "Status {$status->value} should not be able to transition to itself"
            );
        }
    }

    /** @test */
    public function it_provides_correct_labels()
    {
        $expectedLabels = [
            'draft' => 'Borrador',
            'sent' => 'Enviada',
            'confirmed' => 'Confirmada',
            'received' => 'Recibida',
            'cancelled' => 'Cancelada',
        ];

        foreach ($expectedLabels as $value => $label) {
            $status = OrderStatus::from($value);
            $this->assertEquals($label, $status->getLabel(), "Label for {$value} should be {$label}");
        }
    }

    /** @test */
    public function it_provides_correct_colors()
    {
        $expectedColors = [
            'draft' => 'gray',
            'sent' => 'info',
            'confirmed' => 'warning',
            'received' => 'success',
            'cancelled' => 'danger',
        ];

        foreach ($expectedColors as $value => $color) {
            $status = OrderStatus::from($value);
            $this->assertEquals($color, $status->getColor(), "Color for {$value} should be {$color}");
        }
    }

    /** @test */
    public function it_validates_complete_workflow_path()
    {
        // Ruta completa exitosa: draft → sent → confirmed → received
        $this->assertTrue(OrderStatus::DRAFT->canTransitionTo(OrderStatus::SENT));
        $this->assertTrue(OrderStatus::SENT->canTransitionTo(OrderStatus::CONFIRMED));
        $this->assertTrue(OrderStatus::CONFIRMED->canTransitionTo(OrderStatus::RECEIVED));
    }

    /** @test */
    public function it_validates_cancellation_from_any_active_state()
    {
        // Se puede cancelar desde cualquier estado excepto RECEIVED y CANCELLED
        $this->assertTrue(OrderStatus::DRAFT->canTransitionTo(OrderStatus::CANCELLED));
        $this->assertTrue(OrderStatus::SENT->canTransitionTo(OrderStatus::CANCELLED));
        $this->assertTrue(OrderStatus::CONFIRMED->canTransitionTo(OrderStatus::CANCELLED));
        $this->assertFalse(OrderStatus::RECEIVED->canTransitionTo(OrderStatus::CANCELLED));
        $this->assertFalse(OrderStatus::CANCELLED->canTransitionTo(OrderStatus::CANCELLED));
    }

    /** @test */
    public function it_prevents_backwards_transitions()
    {
        // No se puede retroceder en el workflow
        $this->assertFalse(OrderStatus::SENT->canTransitionTo(OrderStatus::DRAFT));
        $this->assertFalse(OrderStatus::CONFIRMED->canTransitionTo(OrderStatus::SENT));
        $this->assertFalse(OrderStatus::RECEIVED->canTransitionTo(OrderStatus::CONFIRMED));
    }

    /** @test */
    public function it_prevents_skipping_states()
    {
        // No se puede saltar estados
        $this->assertFalse(OrderStatus::DRAFT->canTransitionTo(OrderStatus::CONFIRMED));
        $this->assertFalse(OrderStatus::DRAFT->canTransitionTo(OrderStatus::RECEIVED));
        $this->assertFalse(OrderStatus::SENT->canTransitionTo(OrderStatus::RECEIVED));
    }
}
